<?php
namespace App\Pvm\Behavior;

use Enqueue\Client\ProducerInterface;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Enqueue\HandleAsyncTransitionProcessor;
use Formapro\Pvm\Token;
use Formapro\Pvm\Transition;
use function Makasim\Values\get_value;

class NotifyParentProcessBehavior implements Behavior
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
     * @param Token $token
     *
     * @return Transition[]
     */
    public function execute(Token $token)
    {
        $node = $token->getTransition()->getTo();

        $processId = get_value($node, 'parentProcessId');
        $token = get_value($node, 'parentProcessToken');

        $this->producer->send(HandleAsyncTransitionProcessor::TOPIC, [
            'process' => $processId,
            'token' => $token
        ]);
    }
}
