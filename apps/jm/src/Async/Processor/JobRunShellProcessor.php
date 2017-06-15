<?php
namespace App\Async\Processor;

use Enqueue\Client\CommandSubscriberInterface;
use Quartz\App\Async\AsyncJobRunShell;

class JobRunShellProcessor extends \Quartz\App\Async\JobRunShellProcessor implements CommandSubscriberInterface
{
    public static function getSubscribedCommand()
    {
        return AsyncJobRunShell::COMMAND;
    }
}