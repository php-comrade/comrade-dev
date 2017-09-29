<?php
namespace App\Pvm\Behavior;

use App\Commands;
use App\Topics;
use App\JobStatus;
use App\Model\JobResult;
use App\Model\Process;
use App\Storage\JobStorage;
use Comrade\Shared\Model\Job;
use Enqueue\Client\ProducerInterface;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Exception\InterruptExecutionException;
use Formapro\Pvm\Exception\WaitExecutionException;
use Formapro\Pvm\SignalBehavior;
use Formapro\Pvm\Token;
use function Makasim\Values\get_values;
use Quartz\Bridge\Enqueue\EnqueueResponseJob;
use Quartz\Bridge\Scheduler\RemoteScheduler;
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
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @param JobStorage        $jobStorage
     * @param RemoteScheduler   $remoteScheduler
     * @param ProducerInterface $producer
     */
    public function __construct(
        JobStorage $jobStorage,
        RemoteScheduler $remoteScheduler,
        ProducerInterface $producer
    ) {
        $this->jobStorage = $jobStorage;
        $this->remoteScheduler = $remoteScheduler;
        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
        /** @var Process $process */
        $process = $token->getProcess();
        $job = $this->jobStorage->getOneById($process->getTokenJobId($token));
        $policy = $job->getGracePeriodPolicy();

        $quartzJob = JobBuilder::newJob(EnqueueResponseJob::class)->build();
        $trigger = TriggerBuilder::newTrigger()
            ->forJobDetail($quartzJob)
            ->withSchedule(SimpleScheduleBuilder::simpleSchedule())
            ->setJobData([
                'command' => Commands::PVM_HANDLE_ASYNC_TRANSITION,
                'process' => $process->getId(),
                'token' => $token->getId(),
            ])
            ->startAt(new \DateTime(sprintf('now + %d seconds', $policy->getPeriod())))
            ->build();

        $this->remoteScheduler->scheduleJob($trigger, $quartzJob);
        $this->producer->sendEvent(Topics::UPDATE_JOB, get_values($job));

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
            $this->producer->sendEvent(Topics::UPDATE_JOB, get_values($job));

            return ['failed'];
        });
    }
}
