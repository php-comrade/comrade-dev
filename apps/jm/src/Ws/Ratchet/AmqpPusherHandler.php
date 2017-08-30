<?php

namespace App\Ws\Ratchet;

use Gos\Bundle\WebSocketBundle\Event\Events;
use Gos\Bundle\WebSocketBundle\Event\PushHandlerEvent;
use Gos\Bundle\WebSocketBundle\Pusher\AbstractServerPushHandler;
use Gos\Bundle\WebSocketBundle\Pusher\Serializer\MessageSerializer;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Interop\Amqp\AmqpConsumer;
use Interop\Amqp\AmqpContext;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServerInterface;
use React\EventLoop\LoopInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AmqpPusherHandler extends AbstractServerPushHandler
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var WampRouter
     */
    protected $router;

    /**
     * @var  MessageSerializer
     */
    protected $serializer;

    /**
     * @var  EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var AmqpContext
     */
    private $context;

    /**
     * @param AmqpContext              $context
     * @param WampRouter               $router
     * @param MessageSerializer        $serializer
     * @param EventDispatcherInterface $eventDispatcher
     * @param LoggerInterface|null     $logger
     */
    public function __construct(
        AmqpContext $context,
        WampRouter $router,
        MessageSerializer $serializer,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger = null
    ) {
        $this->context = $context;
        $this->router = $router;
        $this->serializer = $serializer;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger === null ? new NullLogger() : $logger;
    }

    public function setConfig(array $config)
    {
        $config = array_replace([
            'queue' => 'pusher',
            'interval' => 100,
            'max' => 10,
        ], $config);

        parent::setConfig($config);
    }

    public function handle(LoopInterface $loop, WampServerInterface $app)
    {
        $queue = $this->context->createQueue($this->getConfig()['queue']);
        $queue->addFlag(AMQP_DURABLE);

        $this->context->declareQueue($queue);

        $consumer = $this->context->createConsumer($queue);
        $consumer->addFlag(AmqpConsumer::FLAG_NOACK);

        $handler = function () use ($consumer, $app) {
            $count = 0;
            while ($amqpMessage = $consumer->receiveNoWait()) {
                try {
                    $message = $this->serializer->deserialize($amqpMessage->getBody());
                    $request = $this->router->match(new Topic($message->getTopic()));
                    $app->onPush($request, $message->getData(), $this->getName());
                    $this->eventDispatcher->dispatch(Events::PUSHER_SUCCESS, new PushHandlerEvent($message, $this));
                } catch (\Exception $e) {
                    $this->logger->error(
                        'AMQP handler failed to ack message', [
                            'exception_message' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'message' => $amqpMessage->getBody(),
                        ]
                    );

                    $this->eventDispatcher->dispatch(Events::PUSHER_FAIL, new PushHandlerEvent($amqpMessage->getBody(), $this));
                }

                if (++$count >= $this->getConfig()['max']) {
                    break;
                }
            }
        };

        $loop->addPeriodicTimer($this->getConfig()['interval']/1000, $handler);
    }

    public function close()
    {
    }
}
