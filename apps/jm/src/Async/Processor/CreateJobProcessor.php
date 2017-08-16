<?php
namespace App\Async\Processor;

use App\Async\Commands;
use App\Async\CreateJob;
use App\Infra\JsonSchema\Errors;
use App\Infra\JsonSchema\SchemaValidator;
use App\Service\CreateJobTemplateService;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Consumption\Result;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Enqueue\Util\JSON;

class CreateJobProcessor implements PsrProcessor, CommandSubscriberInterface
{
    /**
     * @var SchemaValidator
     */
    private $schemaValidator;

    /**
     * @var CreateJobTemplateService
     */
    private $createJobTemplateService;

    /**
     * @param SchemaValidator $schemaValidator
     * @param CreateJobTemplateService $createJobTemplateService
     */
    public function __construct(
        SchemaValidator $schemaValidator,
        CreateJobTemplateService $createJobTemplateService
    ) {
        $this->schemaValidator = $schemaValidator;
        $this->createJobTemplateService = $createJobTemplateService;
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

        $this->createJobTemplateService->create(CreateJob::create($data)->getJobTemplate());

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedCommand()
    {
        return [
            'processorName' => Commands::CREATE_JOB,
            'queueName' => Commands::CREATE_JOB,
            'queueNameHardcoded' => true,
            'exclusive' => true,
        ];
    }
}
