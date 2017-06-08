<?php
namespace App\Async\Processor;

use App\Async\CreateJob;
use App\Async\Topics;
use App\Infra\JsonSchema\Errors;
use App\Infra\JsonSchema\SchemaValidator;
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
     * @param ProcessStorage $processStorage
     * @param CreateProcessForJobService $createProcessForJobService
     * @param ProducerInterface $producer
     */
    public function __construct(
        SchemaValidator $schemaValidator,
        JobTemplateStorage $jobTemplateStorage,
        ProcessStorage $processStorage,
        CreateProcessForJobService $createProcessForJobService,
        ProducerInterface $producer
    ) {
        $this->schemaValidator = $schemaValidator;
        $this->jobTemplateStorage = $jobTemplateStorage;
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
        $processTemplate = $this->createProcessForJobService->createProcess($jobTemplate);

        $this->jobTemplateStorage->insert($jobTemplate);
        $this->processStorage->insert($processTemplate);

        $this->producer->send(Topics::SCHEDULE_JOB, $processTemplate->getId());

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
