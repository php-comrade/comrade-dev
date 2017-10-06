<?php
namespace App\Queue;

use App\Commands;
use App\Infra\JsonSchema\Errors;
use App\Infra\JsonSchema\SchemaValidator;
use App\Service\CreateJobTemplateService;
use Comrade\Shared\Message\CreateJob;
use Comrade\Shared\Message\ScheduleJob;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Consumption\QueueSubscriberInterface;
use Enqueue\Consumption\Result;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Enqueue\Util\JSON;

class CreateJobProcessor implements PsrProcessor, CommandSubscriberInterface, QueueSubscriberInterface
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
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @param SchemaValidator $schemaValidator
     * @param CreateJobTemplateService $createJobTemplateService
     * @param ProducerInterface $producer
     */
    public function __construct(
        SchemaValidator $schemaValidator,
        CreateJobTemplateService $createJobTemplateService,
        ProducerInterface $producer
    ) {
        $this->schemaValidator = $schemaValidator;
        $this->createJobTemplateService = $createJobTemplateService;
        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $psrMessage, PsrContext $psrContext)
    {
        $data = JSON::decode($psrMessage->getBody());
        if ($errors = $this->schemaValidator->validate($data, CreateJob::SCHEMA)) {
            return Result::reject(Errors::toString($errors, 'Message schema validation has failed.'));
        }

        $createJob = CreateJob::create($data);
        $this->createJobTemplateService->create($createJob->getJobTemplate());

        foreach ($createJob->getTriggers() as $trigger) {
            $this->producer->sendCommand(Commands::SCHEDULE_JOB, ScheduleJob::createFor($trigger));
        }

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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedQueues()
    {
        return [Commands::CREATE_JOB];
    }
}
