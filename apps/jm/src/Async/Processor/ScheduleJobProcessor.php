<?php
namespace App\Async\Processor;

use App\Async\Commands;
use App\Service\ScheduleJobService;
use App\Storage\JobTemplateStorage;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Consumption\Result;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Enqueue\Util\JSON;

class ScheduleJobProcessor implements PsrProcessor, CommandSubscriberInterface
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
     * @param JobTemplateStorage $jobTemplateStorage
     * @param ScheduleJobService $scheduleJobService
     */
    public function __construct(JobTemplateStorage $jobTemplateStorage, ScheduleJobService $scheduleJobService) {
        $this->jobTemplateStorage = $jobTemplateStorage;
        $this->scheduleJobService = $scheduleJobService;
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
        if (false == $jobTemplate = $this->jobTemplateStorage->findOne(['templateId' => $data['jobTemplate']])) {
            return self::REJECT;
        }

        $this->scheduleJobService->schedule($jobTemplate, $jobTemplate->getTriggers());

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
