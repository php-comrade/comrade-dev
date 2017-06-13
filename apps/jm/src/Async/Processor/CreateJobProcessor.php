<?php
namespace App\Async\Processor;

use App\Async\Commands;
use App\Async\CreateJob;
use App\Infra\JsonSchema\Errors;
use App\Infra\JsonSchema\SchemaValidator;
use App\Model\ExclusiveJob;
use App\Service\CreateProcessForJobService;
use App\Storage\ExclusiveJobStorage;
use App\Storage\JobStorage;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessStorage;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Client\ProducerV2Interface;
use Enqueue\Consumption\Result;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;
use Enqueue\Util\JSON;

class CreateJobProcessor implements PsrProcessor, CommandSubscriberInterface
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
     * @var ProducerV2Interface
     */
    private $producer;
    /**
     * @var ExclusiveJobStorage
     */
    private $exclusiveJobStorage;

    /**
     * @param SchemaValidator $schemaValidator
     * @param JobTemplateStorage $jobTemplateStorage
     * @param ExclusiveJobStorage $exclusiveJobStorage
     * @param ProcessStorage $processStorage
     * @param CreateProcessForJobService $createProcessForJobService
     * @param ProducerV2Interface $producer
     */
    public function __construct(
        SchemaValidator $schemaValidator,
        JobTemplateStorage $jobTemplateStorage,
        ExclusiveJobStorage $exclusiveJobStorage,
        ProcessStorage $processStorage,
        CreateProcessForJobService $createProcessForJobService,
        ProducerV2Interface $producer
    ) {
        $this->schemaValidator = $schemaValidator;
        $this->jobTemplateStorage = $jobTemplateStorage;
        $this->exclusiveJobStorage = $exclusiveJobStorage;
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

        if ($jobTemplate->getExclusivePolicy()) {
            $exclusiveJob = new ExclusiveJob();
            $exclusiveJob->setName($jobTemplate->getName());

            $this->exclusiveJobStorage->update($exclusiveJob, ['name' => $exclusiveJob->getName()], ['upsert' => true]);
        }

        $this->producer->sendCommand(Commands::SCHEDULE_PROCESS, $processTemplate->getId());

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedCommand()
    {
        return Commands::CREATE_JOB;
    }
}
