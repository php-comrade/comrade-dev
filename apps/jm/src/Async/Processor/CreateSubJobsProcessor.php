<?php
namespace App\Async\Processor;

use App\Async\Commands;
use App\Async\RunSubJobsResult;
use App\Infra\JsonSchema\Errors;
use App\Infra\JsonSchema\SchemaValidator;
use App\Service\CreateProcessForSubJobsService;
use App\Storage\JobStorage;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessExecutionStorage;
use App\Storage\ProcessStorage;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Consumption\Result;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Enqueue\Util\JSON;

class CreateSubJobsProcessor implements PsrProcessor, CommandSubscriberInterface
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
     * @var JobTemplateStorage
     */
    private $jobTemplateStorage;

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
     * @var ProcessExecutionStorage
     */
    private $processExecutionStorage;

    /**
     * @param SchemaValidator $schemaValidator
     * @param JobStorage $jobStorage
     * @param JobTemplateStorage $jobTemplateStorage
     * @param ProcessStorage $processStorage
     * @param ProcessExecutionStorage $processExecutionStorage
     * @param CreateProcessForSubJobsService $createProcessForSubJobsService
     * @param ProducerInterface $producer
     */
    public function __construct(
        SchemaValidator $schemaValidator,
        JobStorage $jobStorage,
        JobTemplateStorage $jobTemplateStorage,
        ProcessStorage $processStorage,
        ProcessExecutionStorage $processExecutionStorage,
        CreateProcessForSubJobsService $createProcessForSubJobsService,
        ProducerInterface $producer
    ) {
        $this->schemaValidator = $schemaValidator;
        $this->jobStorage = $jobStorage;
        $this->jobTemplateStorage = $jobTemplateStorage;
        $this->processStorage = $processStorage;
        $this->processExecutionStorage = $processExecutionStorage;
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
        if ($errors = $this->schemaValidator->validate($data, RunSubJobsResult::SCHEMA)) {
            return Result::reject(Errors::toString($errors, 'Message schema validation has failed.'));
        }



        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedCommand()
    {
        return Commands::CREATE_SUB_JOBS;
    }
}
