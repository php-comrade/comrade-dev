<?php
namespace App\Pvm\Behavior;

use App\Async\ExecuteJob;
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
        $job = $process->getJob(get_value($token->getTransition()->getTo(), 'job.uid'));

        $message = ExecuteJob::create();
        $message->setJob($job);
        $message->setToken($token->getId());

        $queue = $this->psrContext->createQueue(get_value($job, 'enqueue.queue'));
        $message = $this->psrContext->createMessage(JSON::encode($message));

        $this->psrContext->createProducer()->send($queue, $message);

        throw new WaitExecutionException();
    }

    /**
     * {@inheritdoc}
     */
    public function signal(Token $token)
    {
        /** @var JobFeedback $jobFeedback */
        $jobFeedback = get_object($token->getTransition()->getTo(), 'jobFeedback');

        /** @var Process $process */
        $process = $token->getProcess();
        $job = $process->getJob(get_value($token->getTransition()->getTo(), 'job.uid'));

        if (get_value($job, 'timeoutAt')) {
            return;
        }

        if (false == get_value($jobFeedback, 'finished', false)) {
            throw new WaitExecutionException();
        }
    }
}
