<?php
namespace App\Pvm\Behavior;

use App\Model\GracePeriodPolicy;
use App\Model\Process;
use App\Model\RetryFailedPolicy;
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
     * @param ProcessExecutionStorage $processExecutionStorage
     */
    public function __construct(ProcessExecutionStorage $processExecutionStorage)
    {
        $this->processExecutionStorage = $processExecutionStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
        /** @var Process $process */
        $process = $token->getProcess();

        /** @var RetryFailedPolicy $retryFailedPolicy */
        $retryFailedPolicy = get_object($token->getTransition()->getTo(), 'retryFailedPolicy');
        $retryLimit = $retryFailedPolicy->getRetryLimit();
        $job = $process->getJob(get_value($token->getTransition()->getTo(), 'job.uid'));

        if (get_value($job, 'finished', false)) {
            return ['complete'];
        }

        $retryAttempts = get_value($job, 'retryAttempts', 0);
        if ($retryAttempts >= $retryLimit) {
            return ['failed'];
        }

        set_value($job, 'retryAttempts', ++$retryAttempts);

        return ['retry'];
    }
}