<?php
namespace App\Pvm\Behavior;

use App\Commands;
use App\Model\JobAction;
use App\Model\PvmToken;
use App\Service\ChangeJobStateService;
use App\Topics;
use App\Model\JobResult;
use App\Storage\JobStorage;
use Comrade\Shared\Model\Job;
use Enqueue\Client\ProducerInterface;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Exception\InterruptExecutionException;
use Formapro\Pvm\Exception\WaitExecutionException;
use Formapro\Pvm\SignalBehavior;
use Formapro\Pvm\Token;
use Formapro\Pvm\Transition;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use Quartz\Bridge\Enqueue\EnqueueResponseJob;
use Quartz\Bridge\Scheduler\RemoteScheduler;
use Quartz\Core\JobBuilder;
use Quartz\Core\SimpleScheduleBuilder;
use Quartz\Core\TriggerBuilder;

class GracePeriodPolicyBehavior implements Behavior, SignalBehavior
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
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var ChangeJobStateService
     */
    private $changeJobStateService;

    public function __construct(
        RemoteScheduler $remoteScheduler,
        ProducerInterface $producer,
        JobStorage $jobStorage,
        ChangeJobStateService $changeJobStateService
    ) {
        $this->remoteScheduler = $remoteScheduler;
        $this->producer = $producer;
        $this->changeJobStateService = $changeJobStateService;
        $this->jobStorage = $jobStorage;
    }

    /**
     * @param PvmToken $token
     *
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
        $job = $this->jobStorage->getOneById($token->getJobId());
        $policy = $job->getGracePeriodPolicy();

        $quartzJob = JobBuilder::newJob(EnqueueResponseJob::class)->build();
        $trigger = TriggerBuilder::newTrigger()
            ->forJobDetail($quartzJob)
            ->withSchedule(SimpleScheduleBuilder::simpleSchedule())
            ->setJobData([
                'command' => Commands::PVM_HANDLE_ASYNC_TRANSITION,
                'process' => $token->getProcess()->getId(),
                'token' => $token->getId(),
            ])
            ->startAt(new \DateTime(sprintf('now + %d seconds', $policy->getPeriod())))
            ->build();

        $this->remoteScheduler->scheduleJob($trigger, $quartzJob);

        throw new WaitExecutionException;
    }

    /**
     * @param PvmToken $token
     *
     * {@inheritdoc}
     */
    public function signal(Token $token)
    {
        $job = $this->jobStorage->getOneById($token->getJobId());

        if (get_value($job, 'finishedAt')) {
            throw new InterruptExecutionException();
        }

        /** @var Job $job */
        $job = $this->changeJobStateService->changeInFlow($job->getId(), JobAction::FAIL, function(Job $job, Transition $transition) {
            $result = JobResult::createFor($transition->getTo()->getLabel(), new \DateTime('now'));

            $job->addResult($result);
            $job->setCurrentResult($result);

            return $job;
        });

        $this->producer->sendEvent(Topics::JOB_UPDATED, get_values($job));
    }
}
