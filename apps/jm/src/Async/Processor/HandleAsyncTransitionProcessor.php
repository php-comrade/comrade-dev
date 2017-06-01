<?php
namespace App\Async\Processor;

use App\Async\Topics;
use App\Model\Process;
use App\Storage\ProcessExecutionStorage;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;
use Enqueue\Util\JSON;
use Formapro\Pvm\ProcessEngine;
use Psr\Log\NullLogger;

class HandleAsyncTransitionProcessor implements PsrProcessor, TopicSubscriberInterface
{
    /**
     * @var ProcessEngine
     */
    private $processEngine;

    /**
     * @var ProcessExecutionStorage
     */
    private $processExecutionStorage;

    /**
     * @param ProcessEngine $processEngine
     * @param ProcessExecutionStorage $processExecutionStorage
     */
    public function __construct(ProcessEngine $processEngine, ProcessExecutionStorage $processExecutionStorage)
    {
        $this->processEngine = $processEngine;
        $this->processExecutionStorage = $processExecutionStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $psrMessage, PsrContext $psrContext)
    {
        if ($psrMessage->isRedelivered()) {
            return Result::reject('The message failed. Remove it');
        }

        $data = JSON::decode($psrMessage->getBody());

        /** @var Process $process */
        if (false == $process = $this->processExecutionStorage->findOne(['id' => $data['process']])) {
            return Result::reject('Process was not found');
        }

        if (false == $token = $process->getToken($data['token'])) {
            return Result::reject('No such token');
        }

        try {
            $this->processEngine->proceed($token, new NullLogger());
        } finally {
            $this->processExecutionStorage->update($process);
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::PVM_HANDLE_ASYNC_TRANSITION];
    }
}
