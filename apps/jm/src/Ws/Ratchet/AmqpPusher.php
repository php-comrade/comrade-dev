<?php

namespace App\Ws\Ratchet;

use Gos\Bundle\WebSocketBundle\Pusher\AbstractPusher;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpQueue;

class AmqpPusher extends AbstractPusher
{
    /**
     * @var AmqpContext
     */
    private $context;

    /**
     * @var AmqpQueue
     */
    private $queue;

    /**
     * @param AmqpContext $context
     */
    public function __construct(AmqpContext $context)
    {
        $this->context = $context;
    }

    public function setConfig($config)
    {
        $config = array_replace([
            'queue' => 'pusher',
        ], $config);

        parent::setConfig($config);
    }

    /**
     * {@inheritdoc}
     */
    protected function doPush($data, array $context)
    {
        if (null === $this->queue) {
            $this->queue = $this->context->createQueue($this->getConfig()['queue']);
            $this->queue->addFlag(AMQP_DURABLE);

        }

        $message = $this->context->createMessage($data);

        $this->context->createProducer()->send($this->queue, $message);
    }

    public function close()
    {

    }
}
