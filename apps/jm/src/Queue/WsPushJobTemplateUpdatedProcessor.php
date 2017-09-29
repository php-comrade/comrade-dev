<?php

namespace App\Queue;

use App\Topics;
use App\Infra\ThruwayClient;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Util\JSON;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class WsPushJobTemplateUpdatedProcessor implements PsrProcessor, TopicSubscriberInterface
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
    public function process(PsrMessage $message, PsrContext $context)
    {
        if ($message->isRedelivered()) {
            return Result::reject('Rejected redelivered message');
        }

        try {
            $data = JSON::decode($message->getBody());

            $this->client->publish(Topics::JOB_TEMPLATE_UPDATED, $data);

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
        return [Topics::JOB_TEMPLATE_UPDATED];
    }
}
