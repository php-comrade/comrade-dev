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
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Enqueue\Util\JSON;

class CreateJobProcessor implements Processor, CommandSubscriberInterface, QueueSubscriberInterface
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
    public function process(Message $Message, Context $Context)
    {
        $data = JSON::decode($Message->getBody());
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

    public static function getSubscribedCommand(): array
    {
        return [
            'command' => Commands::CREATE_JOB,
            'queue' => Commands::CREATE_JOB,
            'prefix_queue' => false,
            'exclusive' => true,
        ];
    }

    public static function getSubscribedQueues(): array
    {
        return [Commands::CREATE_JOB];
    }
}
