<?php
namespace App\Queue;

use App\Commands;
use App\Infra\JsonSchema\Errors;
use App\Infra\JsonSchema\SchemaValidator;
use App\Service\ScheduleJobService;
use App\Storage\JobTemplateStorage;
use Comrade\Shared\Message\ScheduleJob;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Consumption\Result;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Enqueue\Util\JSON;

class ScheduleJobProcessor implements Processor, CommandSubscriberInterface
{
    /**
     * @var JobTemplateStorage
     */
    private $jobTemplateStorage;

    /**
     * @var ScheduleJobService
     */
    private $scheduleJobService;
    /**
     * @var SchemaValidator
     */
    private $schemaValidator;

    /**
     * @param JobTemplateStorage $jobTemplateStorage
     * @param SchemaValidator $schemaValidator
     * @param ScheduleJobService $scheduleJobService
     */
    public function __construct(
        JobTemplateStorage $jobTemplateStorage,
        SchemaValidator $schemaValidator,
        ScheduleJobService $scheduleJobService
    ) {
        $this->jobTemplateStorage = $jobTemplateStorage;
        $this->schemaValidator = $schemaValidator;
        $this->scheduleJobService = $scheduleJobService;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Message $Message, Context $Context)
    {
        $data = JSON::decode($Message->getBody());
        if ($errors = $this->schemaValidator->validate($data, ScheduleJob::SCHEMA)) {
            return Result::reject(Errors::toString($errors, 'Message schema validation has failed.'));
        }

        $scheduleJob = ScheduleJob::create($data);
        $trigger = $scheduleJob->getTrigger();

        if (false == $jobTemplate = $this->jobTemplateStorage->findOne(['templateId' => $trigger->getTemplateId()])) {
            return self::REJECT;
        }

        $this->scheduleJobService->schedule($jobTemplate, $trigger);

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedCommand()
    {
        return Commands::SCHEDULE_JOB;
    }
}
