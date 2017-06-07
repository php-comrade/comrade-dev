<?php
namespace App\Pvm\Behavior;

use App\Model\GracePeriodPolicy;
use App\Model\Process;
use App\Model\RetryFailedPolicy;
use App\Storage\JobStorage;
use App\Storage\ProcessExecutionStorage;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Token;
use function Makasim\Values\get_object;
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
        $job = $this->jobStorage->getOneById($process->getTokenJobId($token));
        if (false == $job->getCurrentResult()->isFailed()) {
            return ['complete'];
        }

        /** @var RetryFailedPolicy $retryFailedPolicy */
        $retryFailedPolicy = get_object($token->getTransition()->getTo(), 'retryFailedPolicy');
        $retryLimit = $retryFailedPolicy->getRetryLimit();

        $retryAttempts = get_value($job, 'retryAttempts', 0);
        if ($retryAttempts >= $retryLimit) {
            return ['failed'];
        }

        set_value($job, 'retryAttempts', ++$retryAttempts);
        $this->jobStorage->update($job);

        return ['retry'];
    }
}
