<?php
namespace App\Infra\Enqueue;

use Enqueue\Client\Config;
use Enqueue\Client\Message;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Client\ProducerInterface;

class Producer
{
    /**
     * @var ProducerInterface
     */
    private $realProducer;

    /**
     * @var QueueMetaRegistry
     */
    private $queueMetaRegistry;

    /**
     * @param ProducerInterface $realProducer
     * @param QueueMetaRegistry $queueMetaRegistry
     */
    public function __construct(ProducerInterface $realProducer, QueueMetaRegistry $queueMetaRegistry)
    {
        $this->realProducer = $realProducer;
        $this->queueMetaRegistry = $queueMetaRegistry;
    }

    public function sendEvent($topic, $message)
    {
        $this->realProducer->send($topic, $message);
    }

    public function sendCommand($command, $message)
    {
        if (false == $message instanceof Message) {
            $message = new Message($message);
        }

        $message->setProperty(Config::PARAMETER_TOPIC_NAME, '__command__');
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, $command);
        $message->setScope(Message::SCOPE_APP);

        $commandQueueMeta = null;
        foreach ($this->queueMetaRegistry->getQueuesMeta() as $meta) {
            if (in_array($command, $meta->getProcessors())) {
                $commandQueueMeta = $meta;

                break;
            }
        }

        if (false == $commandQueueMeta) {
            throw new \LogicException(sprintf('The queue meta could not be found for command "%s"', $command));
        }

        $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, $commandQueueMeta->getClientName());


        $this->realProducer->send('__command__', $message);
    }
}
