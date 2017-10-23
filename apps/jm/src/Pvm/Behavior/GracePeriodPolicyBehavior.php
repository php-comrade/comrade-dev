<?php
namespace App\Pvm\Behavior;

use App\Commands;
use App\Model\JobAction;
use App\Model\PvmToken;
use App\Service\ChangeJobStateService;
use App\Service\PersistJobService;
use App\Storage\JobStorage;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Exception\InterruptExecutionException;
use Formapro\Pvm\Exception\WaitExecutionException;
use Formapro\Pvm\SignalBehavior;
use Formapro\Pvm\Token;
use function Makasim\Values\get_value;
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
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var ChangeJobStateService
     */
    private $changeJobStateService;

    /**
     * @var PersistJobService
     */
    private $persistJobService;

    public function __construct(
        RemoteScheduler $remoteScheduler,
        JobStorage $jobStorage,
        ChangeJobStateService $changeJobStateService,
        PersistJobService $persistJobService
    ) {
        $this->remoteScheduler = $remoteScheduler;
        $this->changeJobStateService = $changeJobStateService;
        $this->jobStorage = $jobStorage;

        $this->persistJobService = $persistJobService;
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

        $this->changeJobStateService->transitionInFlow($job->getId(), JobAction::FAIL);
    }
}
