<?php
namespace App\Pvm\Behavior;

use App\JobStatus;
use App\Model\Job;
use App\Model\JobResult;
use App\Model\Process;
use App\Storage\JobStorage;
use App\Storage\ProcessExecutionStorage;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Token;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class RetryFailedBehavior implements Behavior
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

        return $this->jobStorage->lockByJobId($process->getTokenJobId($token), function(Job $job) use ($token) {
            if (false == $job->getCurrentResult()->isFailed()) {
                return ['complete'];
            }

            $retryLimit = $job->getRetryFailedPolicy()->getRetryLimit();

            $retryAttempts = get_value($job, 'retryAttempts', 0);
            if ($retryAttempts >= $retryLimit) {
                return ['failed'];
            }

            $jobResult = JobResult::createFor(JobStatus::STATUS_NEW);
            set_value($job, 'retryAttempts', ++$retryAttempts);
            $job->addResult($jobResult);
            $job->setCurrentResult($jobResult);
            $this->jobStorage->update($job);

            return ['retry'];
        });
    }
}
