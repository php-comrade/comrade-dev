<?php
namespace App\Async\Processor;

use Enqueue\Client\CommandSubscriberInterface;
use Quartz\App\RemoteScheduler;

class RemoteSchedulerProcessor extends \Quartz\App\RemoteSchedulerProcessor implements CommandSubscriberInterface
{
    public static function getSubscribedCommand()
    {
        return RemoteScheduler::COMMAND;
    }
}