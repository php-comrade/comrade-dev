<?php
namespace App\Async\Processor;

use App\Async\CreateJob;
use App\Async\Topics;
use App\Infra\JsonSchema\Errors;
use App\Infra\JsonSchema\SchemaValidator;
use App\Infra\Uuid;
use App\Model\Job;
use App\Model\JobResult;
use App\Service\CreateProcessForJobService;
use App\Storage\JobStorage;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessStorage;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;
use Enqueue\Util\JSON;
use function Makasim\Values\get_values;

class CreateJobProcessor implements PsrProcessor, TopicSubscriberInterface
{
    /**
     * @var SchemaValidator
     */
    private $schemaValidator;

    /**
     * @var JobStorage
     */
    private $jobTemplateStorage;

    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var ProcessStorage
     */
    private $processStorage;

    /**
     * @var CreateProcessForJobService
     */
    private $createProcessForJobService;

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @param SchemaValidator $schemaValidator
     * @param JobTemplateStorage $jobTemplateStorage
     * @param JobStorage $jobStorage
     * @param ProcessStorage $processStorage
     * @param CreateProcessForJobService $createProcessForJobService
     * @param ProducerInterface $producer
     */
    public function __construct(
        SchemaValidator $schemaValidator,
        JobTemplateStorage $jobTemplateStorage,
        JobStorage $jobStorage,
        ProcessStorage $processStorage,
        CreateProcessForJobService $createProcessForJobService,
        ProducerInterface $producer
    ) {
        $this->schemaValidator = $schemaValidator;
        $this->jobTemplateStorage = $jobTemplateStorage;
        $this->jobStorage = $jobStorage;
        $this->processStorage = $processStorage;
        $this->createProcessForJobService = $createProcessForJobService;
        $this->producer = $producer;
    }


    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $psrMessage, PsrContext $psrContext)
    {
        if ($psrMessage->isRedelivered()) {
            return Result::reject('The message failed. Remove it');
        }

        $data = JSON::decode($psrMessage->getBody());
        if ($errors = $this->schemaValidator->validate($data, CreateJob::SCHEMA)) {
            return Result::reject(Errors::toString($errors, 'Message schema validation has failed.'));
        }

        $jobTemplate = CreateJob::create($data)->getJobTemplate();
        $this->jobTemplateStorage->insert($jobTemplate);

        $job = Job::createFromTemplate($jobTemplate);
        $job->setId(Uuid::generate());

        $process = $this->createProcessForJobService->createProcess($job);
        $job->setProcessId($process->getId());

        $result = JobResult::create();
        $result->setStatus(Job::STATUS_NEW);
        $result->setCreatedAt(new \DateTime('now'));
        $job->addResult($result);
        $job->setCurrentResult($result);

        // TODO for debugging purposes here
        if ($errors = $this->schemaValidator->validate(get_values($job), Job::SCHEMA)) {
            return Result::reject(Errors::toString($errors, 'Job schema validation has failed.'));
        }

        $this->jobStorage->insert($job);
        $this->processStorage->insert($process);

        $this->producer->send(Topics::SCHEDULE_JOB, $process->getId());

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::CREATE_JOB];
    }
}
