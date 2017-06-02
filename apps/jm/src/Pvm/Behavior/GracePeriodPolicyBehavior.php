<?php
namespace App\Pvm\Behavior;

use App\Model\GracePeriodPolicy;
use App\Model\Process;
use App\Storage\ProcessExecutionStorage;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Token;
use function Makasim\Values\get_object;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class GracePeriodPolicyBehavior implements Behavior
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

        /** @var GracePeriodPolicy $gracePeriodPolicy */
        $gracePeriodPolicy = get_object($token->getTransition()->getTo(), 'gracePeriodPolicy');
        $endsAt = $gracePeriodPolicy->getPeriodEndsAt()->getTimestamp();
        $job = $process->getTokenJob($token);

        $this->processExecutionStorage->update($token->getProcess());
        while (time() < $endsAt) {
            sleep(1);
        }

        $reloadedProcess = $this->processExecutionStorage->findOne(['id' => $process->getId()]);

        $job = $reloadedProcess->getJob($job->getUid());
        if ($job->isCompleted() || $job->isCanceled() || $job->isTerminated()) {
            return ['completed'];
        }

        return ['failed'];
    }
}
