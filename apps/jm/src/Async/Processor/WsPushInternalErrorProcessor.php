<?php

namespace App\Async\Processor;

use App\Async\Topics;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Util\JSON;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Voryx\ThruwayBundle\Client\ClientManager;

class WsPushInternalErrorProcessor implements PsrProcessor, TopicSubscriberInterface
{
    /**
     * @var ClientManager
     */
    private $client;

    /**
     * @param ClientManager $client
     */
    public function __construct(ClientManager $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $message, PsrContext $context)
    {
        if ($message->isRedelivered()) {
            return Result::reject('Rejected redelivered message');
        }

        try {
            $data = JSON::decode($message->getBody());

            $this->client->publish(Topics::INTERNAL_ERROR, $data);

            return self::ACK;
        } catch (\Throwable $e) {
            return Result::reject($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::INTERNAL_ERROR];
    }
}
