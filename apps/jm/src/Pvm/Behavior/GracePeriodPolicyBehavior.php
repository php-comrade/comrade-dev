<?php
namespace App\Pvm\Behavior;

use App\Async\Topics;
use App\JobStatus;
use App\Model\Job;
use App\Model\JobResult;
use App\Model\Process;
use App\Storage\JobStorage;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Exception\InterruptExecutionException;
use Formapro\Pvm\Exception\WaitExecutionException;
use Formapro\Pvm\SignalBehavior;
use Formapro\Pvm\Token;
use Quartz\App\EnqueueResponseJob;
use Quartz\App\RemoteScheduler;
use Quartz\Core\JobBuilder;
use Quartz\Core\SimpleScheduleBuilder;
use Quartz\Core\TriggerBuilder;

class GracePeriodPolicyBehavior implements Behavior, SignalBehavior
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var RemoteScheduler
     */
    private $remoteScheduler;

    /**
     * @param JobStorage $jobStorage
     * @param RemoteScheduler $remoteScheduler
     */
    public function __construct(
        JobStorage $jobStorage,
        RemoteScheduler $remoteScheduler
    ) {
        $this->jobStorage = $jobStorage;
        $this->remoteScheduler = $remoteScheduler;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
        /** @var Process $process */
        $process = $token->getProcess();
//        $job = $this->jobStorage->getOneById($process->getTokenJobId($token));
//
//        $endsAt = $job->getGracePeriodPolicy()->getPeriodEndsAt()->getTimestamp();
//        $endsAt = time() + 30;


        $job = JobBuilder::newJob(EnqueueResponseJob::class)->build();
        $trigger = TriggerBuilder::newTrigger()
            ->forJobDetail($job)
            ->withSchedule(SimpleScheduleBuilder::simpleSchedule()->repeatForever())
            ->setJobData([
                'topic' => Topics::PVM_HANDLE_ASYNC_TRANSITION,
                'process' => $process->getId(),
                'token' => $token->getId(),
            ])
            ->startAt(new \DateTime('now + 30 seconds'))
            ->build();

        $this->remoteScheduler->scheduleJob($trigger, $job);

        throw new WaitExecutionException;
    }

    /**
     * {@inheritdoc}
     */
    public function signal(Token $token)
    {
        /** @var Process $process */
        $process = $token->getProcess();

        return $this->jobStorage->lockByJobId($process->getTokenJobId($token), function(Job $job) {
            $result = $job->getCurrentResult();
            if ($result->isDone() || $result->isRunSubJobs() || $result->isRunningSubJobs()) {
                throw new InterruptExecutionException();
            }

            $jobResult = JobResult::createFor(JobStatus::STATUS_FAILED);
            $job->addResult($jobResult);
            $job->setCurrentResult($jobResult);

            $this->jobStorage->update($job);

            return ['failed'];
        });
    }
}
