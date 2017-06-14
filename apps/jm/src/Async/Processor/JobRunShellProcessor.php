<?php
namespace App\Async\Processor;

use Enqueue\Client\TopicSubscriberInterface;
use Quartz\App\Async\AsyncJobRunShell;

class JobRunShellProcessor extends \Quartz\App\Async\JobRunShellProcessor implements TopicSubscriberInterface
{
    public static function getSubscribedTopics()
    {
        return [AsyncJobRunShell::TOPIC];
    }
}