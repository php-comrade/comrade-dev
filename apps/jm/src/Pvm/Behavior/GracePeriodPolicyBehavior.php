<?php
namespace App\Pvm\Behavior;

use App\Model\GracePeriodPolicy;
use App\Model\Process;
use App\Storage\JobStorage;
use App\Storage\ProcessExecutionStorage;
use Formapro\Pvm\Behavior;
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

        /** @var GracePeriodPolicy $gracePeriodPolicy */
        $gracePeriodPolicy = get_object($token->getTransition()->getTo(), 'gracePeriodPolicy');
        $endsAt = $gracePeriodPolicy->getPeriodEndsAt()->getTimestamp();

        $this->processExecutionStorage->update($token->getProcess());
        while (time() < $endsAt) {
            sleep(1);
        }

        $job = $this->jobStorage->getOneById($process->getTokenJobId($token));
        $result = $job->getCurrentResult();
        if ($result->isCompleted() || $result->isCanceled() || $result->isTerminated()) {
            return ['completed'];
        }

        return ['failed'];
    }
}
