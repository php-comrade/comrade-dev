<?php
namespace App\Pvm\Behavior;

use App\Model\GracePeriodPolicy;
use App\Model\Job;
use App\Model\JobResult;
use App\Model\Process;
use App\Storage\JobStorage;
use App\Storage\ProcessExecutionStorage;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Exception\InterruptExecutionException;
use Formapro\Pvm\Token;
use function Makasim\Values\get_object;

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

        $endsAt = $this->getGracePeriodPolicy($token)->getPeriodEndsAt()->getTimestamp();

        $this->processExecutionStorage->update($token->getProcess());
        while (time() < $endsAt) {
            sleep(1);
        }

        $this->jobStorage->lockByJobId($process->getTokenJobId($token), function(Job $job) {
            $result = $job->getCurrentResult();
            if ($result->isRunSubJobs() || $result->isCompleted() || $result->isCanceled() || $result->isTerminated() || $result->isFailed()) {
                throw new InterruptExecutionException();
            }

            $jobResult = JobResult::createFor(Job::STATUS_FAILED);
            $job->addResult($jobResult);
            $job->setCurrentResult($jobResult);

            $this->jobStorage->update($job);

            return ['failed'];
        });
    }

    /**
     * @param Token $token
     *
     * @return GracePeriodPolicy|object
     */
    private function getGracePeriodPolicy(Token $token):GracePeriodPolicy
    {
        return get_object($token->getTransition()->getTo(), 'gracePeriodPolicy');
    }
}
