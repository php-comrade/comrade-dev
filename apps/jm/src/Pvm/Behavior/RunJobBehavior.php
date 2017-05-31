<?php
namespace App\Pvm\Behavior;

use App\Async\ExecuteJob;
use App\Model\Job;
use Enqueue\Psr\PsrContext;
use Enqueue\Util\JSON;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Exception\WaitExecutionException;
use Formapro\Pvm\SignalBehavior;
use Formapro\Pvm\Token;
use function Makasim\Values\get_value;

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
        $transition = $token->getTransition();

        /** @var Job $job */
        $job = $transition->getTo()->getObject('job');

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
        $transition = $token->getTransition();

        /** @var Job $job */
        $job = $transition->getTo()->getObject('job');

        if (false == get_value($job, 'finished', false)) {
            throw new WaitExecutionException();
        }
    }
}
