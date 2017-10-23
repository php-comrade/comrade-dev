<?php
namespace App\Pvm\Behavior;

use App\Commands;
use App\Model\PvmToken;
use Comrade\Shared\Model\SubJobTrigger;
use Enqueue\Client\ProducerInterface;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Token;

class NotifyParentProcessBehavior implements Behavior
{
    /**
     * @var ProducerInterface
     */
    private $producer;

    public function __construct(ProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    /**
     * @param PvmToken $token
     *
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
        /** @var SubJobTrigger $trigger */
        $trigger = $token->getProcess()->getTrigger();
        if (false == $trigger instanceof SubJobTrigger) {
            throw new \LogicException(sprintf('The trigger must be instance of "%s" but got "%s"', SubJobTrigger::class, get_class($trigger)));
        }

        $this->producer->sendCommand(Commands::PVM_HANDLE_ASYNC_TRANSITION, [
            'token' => $trigger->getParentToken(),
        ]);

        return $token->getTransition()->getName();
    }
}
