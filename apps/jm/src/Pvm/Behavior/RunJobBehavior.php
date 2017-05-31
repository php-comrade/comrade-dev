<?php
namespace App\Pvm\Behavior;

use App\Async\ExecuteJob;
use App\Async\Topics;
use App\Model\Job;
use Enqueue\Client\ProducerInterface;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Exception\WaitExecutionException;
use Formapro\Pvm\SignalBehavior;
use Formapro\Pvm\Token;

class RunJobBehavior implements Behavior, SignalBehavior
{
    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @param ProducerInterface $producer
     */
    public function __construct(ProducerInterface $producer)
    {
        $this->producer = $producer;
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

        $this->producer->send(Topics::EXECUTE_JOB, $message);

        throw new WaitExecutionException();
    }

    /**
     * {@inheritdoc}
     */
    public function signal(Token $token)
    {

    }
}
