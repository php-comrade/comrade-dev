<?php
namespace App\Service;

use App\Commands;
use App\Message\ExecuteJob;
use App\Storage\TriggerStorage;
use Comrade\Shared\Model\CronTrigger;
use App\Model\JobTemplate;
use Comrade\Shared\Model\NowTrigger;
use Comrade\Shared\Model\SimpleTrigger;
use Comrade\Shared\Model\Trigger;
use Enqueue\Client\ProducerInterface;
use function Makasim\Values\get_values;
use Quartz\Bridge\Enqueue\EnqueueResponseJob;
use Quartz\Bridge\Scheduler\RemoteScheduler;
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
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var TriggerStorage
     */
    private $triggerStorage;

    /**
     * @param RemoteScheduler $remoteScheduler
     * @param ProducerInterface $producer
     * @param TriggerStorage $triggerStorage
     */
    public function __construct(
        RemoteScheduler $remoteScheduler,
        ProducerInterface $producer,
        TriggerStorage $triggerStorage
    ) {
        $this->remoteScheduler = $remoteScheduler;
        $this->producer = $producer;
        $this->triggerStorage = $triggerStorage;
    }

    /**
     * @param JobTemplate $jobTemplate
     * @param Trigger $trigger
     */
    public function schedule(JobTemplate $jobTemplate, Trigger $trigger):void
    {
        if ($trigger instanceof SimpleTrigger) {
            $this->triggerStorage->insert($trigger);

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
                ->setJobData(get_values(ExecuteJob::createFor($trigger)))
                ->build();

            $quartzTrigger->setMisfireInstruction($misfireInstructionsMap[$trigger->getMisfireInstruction()]);
            if ($trigger->getStartAt()) {
                $quartzTrigger->setStartTime($trigger->getStartAt());
            }

            $this->remoteScheduler->scheduleJob($quartzTrigger, $job);

            return;
        }

        if ($trigger instanceof CronTrigger) {
            $this->triggerStorage->insert($trigger);

            $misfireInstructionsMap = [
                CronTrigger::MISFIRE_INSTRUCTION_FIRE_ONCE_NOW => QuartzCronTrigger::MISFIRE_INSTRUCTION_FIRE_ONCE_NOW,
                CronTrigger::MISFIRE_INSTRUCTION_DO_NOTHING => QuartzCronTrigger::MISFIRE_INSTRUCTION_DO_NOTHING,
                CronTrigger::MISFIRE_INSTRUCTION_SMART_POLICY => QuartzCronTrigger::MISFIRE_INSTRUCTION_SMART_POLICY,
                CronTrigger::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY => QuartzCronTrigger::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY,
            ];

            $quartzScheduleBuilder = CronScheduleBuilder::cronSchedule($trigger->getQuartzExpression());

            $job = JobBuilder::newJob(EnqueueResponseJob::class)->build();
            $quartzTrigger = TriggerBuilder::newTrigger()
                ->forJobDetail($job)
                ->withSchedule($quartzScheduleBuilder)
                ->setJobData(get_values(ExecuteJob::createFor($trigger)))
                ->build();

            $quartzTrigger->setMisfireInstruction($misfireInstructionsMap[$trigger->getMisfireInstruction()]);
            if ($trigger->getStartAt()) {
                $quartzTrigger->setStartTime($trigger->getStartAt());
            }

            $this->remoteScheduler->scheduleJob($quartzTrigger, $job);

            return;
        }

        if ($trigger instanceof NowTrigger) {
            $this->triggerStorage->insert($trigger);

            $this->producer->sendCommand(Commands::EXECUTE_JOB, ExecuteJob::createFor($trigger));

            return;
        }

        throw new \LogicException(sprintf('Trigger "%s" is not supported', get_class($trigger)));
    }
}
