<?php
namespace App\Infra\Enqueue;

use Enqueue\Client\ConsumptionExtension\DelayRedeliveredMessageExtension;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\MessageReceivedExtensionInterface;
use Enqueue\Consumption\Result;
use Interop\Amqp\AmqpContext;
use Interop\Queue\Context;

class LimitMessageRedeliveryExtension implements MessageReceivedExtensionInterface
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var int
     */
    private $limitRetries;

    public function __construct(Context $context, int $limitRetries)
    {
        $this->context = $context;
        $this->limitRetries = $limitRetries;
    }

    public function onMessageReceived(MessageReceived $context): void
    {
        $message = $context->getMessage();

        $count = $message->getProperty(DelayRedeliveredMessageExtension::PROPERTY_REDELIVER_COUNT, 0);
        if ($count <= $this->limitRetries) {
            return;
        }

        $rejectedQueue = $this->context->createQueue('comrade.rejected');

        if ($this->context instanceof AmqpContext) {
            $this->context->declareQueue($rejectedQueue);
        }

        $this->context->createProducer()
            ->setTimeToLive(3600)
            ->send($rejectedQueue, $message)
        ;

        $context->setResult(Result::reject(sprintf('A message was redelivered more than %d times.', $this->limitRetries)));
        $context->getLogger()->debug(
            '[RejectRedeliveredMessageAfterSomeAttemptsExtension] '.
            sprintf('A message was redelivered more than %d', $this->limitRetries)
        );
    }
}
