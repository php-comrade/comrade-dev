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

class WsPushJobUpdatedProcessor implements PsrProcessor, TopicSubscriberInterface
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

        $data = JSON::decode($message->getBody());

        $this->client->publish(Topics::UPDATE_JOB, $data);

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::UPDATE_JOB];
    }
}
