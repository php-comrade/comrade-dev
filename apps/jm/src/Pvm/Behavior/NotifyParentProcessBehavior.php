<?php
namespace App\Pvm\Behavior;

use Enqueue\Client\ProducerV2Interface;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Enqueue\HandleAsyncTransitionProcessor;
use Formapro\Pvm\Token;
use Formapro\Pvm\Transition;
use function Makasim\Values\get_value;

class NotifyParentProcessBehavior implements Behavior
{
    /**
     * @var ProducerV2Interface
     */
    private $producer;

    /**
     * @param ProducerV2Interface $producer
     */
    public function __construct(ProducerV2Interface $producer)
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

        $this->producer->sendEvent(HandleAsyncTransitionProcessor::TOPIC, [
            'process' => $processId,
            'token' => $token
        ]);
    }
}
