<?php
namespace App\Pvm\Behavior;

use App\Commands;
use App\Model\Job;
use App\Model\JobAction;
use App\Model\PvmProcess;
use App\Model\PvmToken;
use App\Service\ChangeJobStateService;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessExecutionStorage;
use App\JobStatus;
use App\Storage\JobStorage;
use Enqueue\Client\ProducerInterface;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Exception\WaitExecutionException;
use Formapro\Pvm\SignalBehavior;
use Formapro\Pvm\Token;
use function Makasim\Values\array_set;

class WaitSubJobsProcessBehavior implements Behavior, SignalBehavior
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var JobTemplateStorage
     */
    private $jobTemplateStorage;

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var ChangeJobStateService
     */
    private $changeJobStateService;

    /**
     * @var ProcessExecutionStorage
     */
    private $processExecutionStorage;

    public function __construct(
        JobStorage $jobStorage,
        JobTemplateStorage $jobTemplateStorage,
        ChangeJobStateService $changeJobStateService,
        ProcessExecutionStorage $processExecutionStorage,
        ProducerInterface $producer
    )
    {
        $this->jobStorage = $jobStorage;
        $this->changeJobStateService = $changeJobStateService;
        $this->producer = $producer;
        $this->jobTemplateStorage = $jobTemplateStorage;
        $this->processExecutionStorage = $processExecutionStorage;
    }

    /**
     * @param PvmToken $token
     *
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
        if ($token->hasRunnerResult()) {
            throw new WaitExecutionException();
        }

        $job = $this->jobStorage->getOneById($token->getJobId());
        if ($job->getRunSubJobsPolicy()->isFinished()) {
            return;
        }

        $isFinishedNow = $this->jobStorage->lockByJobId($token->getJobId(), function(Job $job) use ($token) {
            $policy = $job->getRunSubJobsPolicy();
            if ($policy->isFinished()) {
                return false;
            }

            $policy->setFinishedSubJobsCount($this->jobStorage->countFinishedSubJobs($job->getId()));
            if ($policy->getCreatedSubJobsCount() === $policy->getFinishedSubJobsCount()) {
                $policy->setFinished(true);
            }

            $this->jobStorage->update($job);

            return $policy->isFinished();
        });

        if ($isFinishedNow) {
            $tokenWithRunnerResult = $this->findTokenWithRunnerResult($token->getProcess());

            $this->producer->sendCommand(Commands::PVM_HANDLE_ASYNC_TRANSITION, [
                'token' => $tokenWithRunnerResult->getId(),
            ]);
        }
    }

    /**
     * @param PvmToken $token
     *
     * {@inheritdoc}
     */
    public function signal(Token $token)
    {
        if (false == $token->hasRunnerResult()) {
            return;
        }

        $job = $this->jobStorage->getOneById($token->getJobId());
        $subJobsResult = [];

        foreach ($this->jobStorage->findSubJobs($job->getId()) as $subJob) {
            $subJobsResult[] = $subJob->getResultPayload();

            if ($subJob->getCurrentResult()->getStatus() === JobStatus::FAILED && $job->getRunSubJobsPolicy()->isMarkParentJobAsFailed()) {
                $this->changeJobStateService->transitionInFlow($job->getId(), JobAction::FAIL);

                return 'finalize';
            }
        }

        $this->changeJobStateService->transitionInFlow($job->getId(), JobAction::COMPLETE);

        if ($resultPayloadKey = $job->getRunSubJobsPolicy()->getResultPayloadKey()) {
            $payloadResult = $job->getResultPayload();
            if (null === $payloadResult) {
                $payloadResult = [];
            }

            if (false == is_array($payloadResult)) {
                throw new \LogicException('Cannot set sub jobs result to parent job result cuz parent job result is not array');
            }

            array_set($resultPayloadKey, $subJobsResult, $payloadResult);
            $job->setResultPayload($payloadResult);

            $this->jobStorage->update($job);
        }

        return 'finalize';
    }

    private function findTokenWithRunnerResult(PvmProcess $process): PvmToken
    {
        foreach ($process->getTokens() as $token) {
            if ($token->hasRunnerResult()) {
                return $token;
            }
        }

        throw new \LogicException('The token with runner result has not been found');
    }
}
