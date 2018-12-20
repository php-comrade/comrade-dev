<?php

namespace App\Queue;

use App\Topics;
use App\Infra\ThruwayClient;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Util\JSON;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;

class WsPushInternalErrorProcessor implements Processor, TopicSubscriberInterface
{
    /**
     * @var ThruwayClient
     */
    private $client;

    /**
     * @param ThruwayClient $client
     */
    public function __construct(ThruwayClient $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Message $message, Context $context)
    {
        if ($message->isRedelivered()) {
            return Result::ack('Rejected redelivered message');
        }

        try {
            $data = JSON::decode($message->getBody());

            $this->client->publish(Topics::INTERNAL_ERROR, $data);

            return self::ACK;
        } catch (\Throwable $e) {
            return Result::ack($e->getMessage());
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
