<?php
namespace App\Pvm\Behavior;

use App\Async\ExecuteJob;
use App\Model\Job;
use App\Model\JobFeedback;
use App\Model\Process;
use Enqueue\Psr\PsrContext;
use Enqueue\Util\JSON;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Exception\WaitExecutionException;
use Formapro\Pvm\SignalBehavior;
use Formapro\Pvm\Token;
use function Makasim\Values\get_object;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;

class RunJobBehavior implements Behavior, SignalBehavior
{
    /**
     * @var PsrContext
     */
    private $psrContext;

    /**
     * @param PsrContext $psrContext
     */
    public function __construct(PsrContext $psrContext)
    {
        $this->psrContext = $psrContext;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
        /** @var Process $process */
        $process = $token->getProcess();
        $job = $process->getTokenJob($token);

        $message = ExecuteJob::create();
        $message->setJob($job);
        $message->setToken($token->getId());

        $queue = $this->psrContext->createQueue(get_value($job, 'enqueue.queue'));
        $message = $this->psrContext->createMessage(JSON::encode($message));

        $job->setStatus(Job::STATUS_RUNNING);

        $this->psrContext->createProducer()->send($queue, $message);

        throw new WaitExecutionException();
    }

    /**
     * {@inheritdoc}
     */
    public function signal(Token $token)
    {
        /** @var Process $process */
        $process = $token->getProcess();
        $job = $process->getTokenJob($token);

        if ($job->isFailed()) {
            return ['failed'];
        }

        if ($job->isCompleted() || $job->isCanceled() || $job->isTerminated()){
            return ['completed'];
        }

        if ($job->isRunning() || $job->isNew()) {
            throw new WaitExecutionException();
        }

        throw new \LogicException(sprintf('Status "%s"is not supported', $job->getStatus()));
    }
}
