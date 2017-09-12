<?php
namespace App\Infra\Enqueue;

use Enqueue\Client\ConsumptionExtension\DelayRedeliveredMessageExtension;
use Enqueue\Consumption\Context;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\Result;
use Interop\Amqp\AmqpContext;
use Interop\Queue\PsrContext;

class LimitMessageRedeliveryExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @var int
     */
    private $limitRetries;

    public function __construct(PsrContext $context, int $limitRetries)
    {
        $this->context = $context;
        $this->limitRetries = $limitRetries;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        $message = $context->getPsrMessage();

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
