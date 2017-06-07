<?php
namespace App\Async\Processor;

use App\Async\CreateSubJobs;
use App\Async\Topics;
use App\Infra\JsonSchema\Errors;
use App\Infra\JsonSchema\SchemaValidator;
use App\Model\Job;
use App\Service\CreateProcessForJobService;
use App\Service\CreateProcessForSubJobsService;
use App\Storage\JobStorage;
use App\Storage\ProcessStorage;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;
use Enqueue\Util\JSON;

class CreateSubJobsProcessor implements PsrProcessor, TopicSubscriberInterface
{
    /**
     * @var SchemaValidator
     */
    private $schemaValidator;

    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var ProcessStorage
     */
    private $processStorage;

    /**
     * @var CreateProcessForSubJobsService
     */
    private $createProcessForSubJobsService;
    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @param SchemaValidator $schemaValidator
     * @param JobStorage $jobStorage
     * @param ProcessStorage $processStorage
     * @param CreateProcessForSubJobsService $createProcessForSubJobsService
     * @param ProducerInterface $producer
     */
    public function __construct(
        SchemaValidator $schemaValidator,
        JobStorage $jobStorage,
        ProcessStorage $processStorage,
        CreateProcessForSubJobsService $createProcessForSubJobsService,
        ProducerInterface $producer
    ) {
        $this->schemaValidator = $schemaValidator;
        $this->jobStorage = $jobStorage;
        $this->processStorage = $processStorage;
        $this->createProcessForSubJobsService = $createProcessForSubJobsService;
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
        if ($errors = $this->schemaValidator->validate($data, CreateSubJobs::SCHEMA)) {
            return Result::reject(Errors::toString($errors, 'Message schema validation has failed.'));
        }

        $message = CreateSubJobs::create($data);
//        if (false == $process = $this->processStorage->findOne(['uid' => $message->getParentProcessUid()])) {
//            return Result::reject(sprintf('The process with uid "%s" could not be found.', $message->getParentProcessUid()));
//        }

        if (false == $parentJob = $this->jobStorage->findOne(['uid' => $message->getParentJobUid()])) {
            return Result::reject(sprintf('The parent job with uid "%s" could not be found.', $message->getParentJobUid()));
        }

        $jobs = [];
        foreach ($message->getSubJobTemplates() as $jobTemplate) {
            $job = Job::createFromTemplate($jobTemplate);

            $jobs[] = $job;

            $this->jobStorage->update($job, ['uid' => $job->getId()], ['upsert' => true]);
        }

        $process = $this->createProcessForSubJobsService->createProcess($jobs);
        $this->processStorage->insert($process);

        $this->producer->send(Topics::SCHEDULE_JOB, $process->getId());

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::CREATE_SUB_JOBS];
    }
}
