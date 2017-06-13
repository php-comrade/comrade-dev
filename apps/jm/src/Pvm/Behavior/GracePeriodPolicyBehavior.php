<?php
namespace App\Pvm\Behavior;

use App\JobStatus;
use App\Model\Job;
use App\Model\JobResult;
use App\Model\Process;
use App\Storage\JobStorage;
use App\Storage\ProcessExecutionStorage;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Exception\InterruptExecutionException;
use Formapro\Pvm\Token;

class GracePeriodPolicyBehavior implements Behavior
{
    /**
     * @var ProcessExecutionStorage
     */
    private $processExecutionStorage;

    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @param ProcessExecutionStorage $processExecutionStorage
     * @param JobStorage $jobStorage
     */
    public function __construct(
        ProcessExecutionStorage $processExecutionStorage,
        JobStorage $jobStorage
    ) {
        $this->processExecutionStorage = $processExecutionStorage;
        $this->jobStorage = $jobStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
        /** @var Process $process */
        $process = $token->getProcess();
        $job = $this->jobStorage->getOneById($process->getTokenJobId($token));

        $endsAt = $job->getGracePeriodPolicy()->getPeriodEndsAt()->getTimestamp();

        $this->processExecutionStorage->update($token->getProcess());
        while (time() < $endsAt) {
            sleep(1);
        }

        return $this->jobStorage->lockByJobId($process->getTokenJobId($token), function(Job $job) {
            $result = $job->getCurrentResult();
            if ($result->isRunSubJobs() || $result->isCompleted() || $result->isCanceled() || $result->isTerminated() || $result->isFailed()) {
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
