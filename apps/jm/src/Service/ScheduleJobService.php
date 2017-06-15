<?php
namespace App\Service;

use App\Async\Commands;
use App\Model\CronTrigger;
use App\Model\JobTemplate;
use App\Model\SimpleTrigger;
use Quartz\App\EnqueueResponseJob;
use Quartz\App\RemoteScheduler;
use Quartz\Core\CronScheduleBuilder;
use Quartz\Core\JobBuilder;
use Quartz\Core\SimpleScheduleBuilder;
use Quartz\Core\TriggerBuilder;
use Quartz\Triggers\SimpleTrigger as QuartzSimpleTrigger;
use Quartz\Triggers\CronTrigger as QuartzCronTrigger;

class ScheduleJobService
{
    /**
     * @var RemoteScheduler
     */
    private $remoteScheduler;

    /**
     * @param RemoteScheduler $remoteScheduler
     */
    public function __construct(RemoteScheduler $remoteScheduler)
    {
        $this->remoteScheduler = $remoteScheduler;
    }

    public function schedule(JobTemplate $jobTemplate):void
    {
        foreach ($jobTemplate->getTriggers() as $trigger) {
            if ($trigger instanceof SimpleTrigger) {
                $misfireInstructionsMap = [
                    SimpleTrigger::MISFIRE_INSTRUCTION_FIRE_NOW => QuartzSimpleTrigger::MISFIRE_INSTRUCTION_FIRE_NOW,
                    SimpleTrigger::MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_EXISTING_COUNT => QuartzSimpleTrigger::MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_EXISTING_COUNT,
                    SimpleTrigger::MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_REMAINING_COUNT => QuartzSimpleTrigger::MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_REMAINING_COUNT,
                    SimpleTrigger::MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_EXISTING_REPEAT_COUNT => QuartzSimpleTrigger::MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_EXISTING_REPEAT_COUNT,
                    SimpleTrigger::MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_REMAINING_REPEAT_COUNT => QuartzSimpleTrigger::MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_REMAINING_REPEAT_COUNT,
                    SimpleTrigger::MISFIRE_INSTRUCTION_SMART_POLICY => QuartzSimpleTrigger::MISFIRE_INSTRUCTION_SMART_POLICY,
                    SimpleTrigger::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY => QuartzSimpleTrigger::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY,
                ];

                $quartzScheduleBuilder = SimpleScheduleBuilder::simpleSchedule()
                    ->withRepeatCount($trigger->getRepeatCount())
                    ->withIntervalInSeconds($trigger->getIntervalInSeconds())
                ;

                $job = JobBuilder::newJob(EnqueueResponseJob::class)->build();
                $quartzTrigger = TriggerBuilder::newTrigger()
                    ->forJobDetail($job)
                    ->withSchedule($quartzScheduleBuilder)
                    ->setJobData([
                        'command' => Commands::EXECUTE_JOB,
                        'jobTemplate' => $jobTemplate->getTemplateId(),
                    ])
                    ->build();

                $quartzTrigger->setMisfireInstruction($misfireInstructionsMap[$trigger->getMisfireInstruction()]);
                if ($trigger->getStartAt()) {
                    $quartzTrigger->setStartTime($trigger->getStartAt());
                }

                $this->remoteScheduler->scheduleJob($quartzTrigger, $job);
            }

            if ($trigger instanceof CronTrigger) {
                $misfireInstructionsMap = [
                    CronTrigger::MISFIRE_INSTRUCTION_FIRE_ONCE_NOW => QuartzCronTrigger::MISFIRE_INSTRUCTION_FIRE_ONCE_NOW,
                    CronTrigger::MISFIRE_INSTRUCTION_DO_NOTHING => QuartzCronTrigger::MISFIRE_INSTRUCTION_DO_NOTHING,
                    CronTrigger::MISFIRE_INSTRUCTION_SMART_POLICY => QuartzCronTrigger::MISFIRE_INSTRUCTION_SMART_POLICY,
                    CronTrigger::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY => QuartzCronTrigger::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY,
                ];

                $quartzScheduleBuilder = CronScheduleBuilder::cronSchedule($trigger->getExpression());

                $job = JobBuilder::newJob(EnqueueResponseJob::class)->build();
                $quartzTrigger = TriggerBuilder::newTrigger()
                    ->forJobDetail($job)
                    ->withSchedule($quartzScheduleBuilder)
                    ->setJobData([
                        'command' => Commands::EXECUTE_JOB,
                        'jobTemplate' => $jobTemplate->getTemplateId(),
                    ])
                    ->build();

                $quartzTrigger->setMisfireInstruction($misfireInstructionsMap[$trigger->getMisfireInstruction()]);
                if ($trigger->getStartAt()) {
                    $quartzTrigger->setStartTime($trigger->getStartAt());
                }

                $this->remoteScheduler->scheduleJob($quartzTrigger, $job);
            }
        }
    }
}
