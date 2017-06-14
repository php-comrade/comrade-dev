<?php
namespace App\Async\Processor;

use Enqueue\Client\TopicSubscriberInterface;
use Quartz\App\RemoteScheduler;

class RemoteSchedulerProcessor extends \Quartz\App\RemoteSchedulerProcessor implements TopicSubscriberInterface
{
    public static function getSubscribedTopics()
    {
        return [RemoteScheduler::TOPIC];
    }
}