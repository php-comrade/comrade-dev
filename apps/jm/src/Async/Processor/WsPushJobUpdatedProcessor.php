<?php

namespace App\Async\Processor;

use App\Async\Topics;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Util\JSON;
use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class WsPushJobUpdatedProcessor implements PsrProcessor, TopicSubscriberInterface
{
    /**
     * @var PusherInterface
     */
    private $pusher;

    /**
     * @param PusherInterface $pusher
     */
    public function __construct(PusherInterface $pusher)
    {
        $this->pusher = $pusher;
    }

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $message, PsrContext $context)
    {
        $data = JSON::decode($message->getBody());

        $this->pusher->push([
            'EVENT' => Topics::UPDATE_JOB,
            'MESSAGE' => $data,
        ], 'events');

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
