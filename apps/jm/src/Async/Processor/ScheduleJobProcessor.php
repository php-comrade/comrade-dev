<?php
namespace App\Async\Processor;

use App\Async\Topics;
use App\Model\Process;
use App\Storage\ProcessExecutionStorage;
use App\Storage\ProcessStorage;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;
use Formapro\Pvm\ProcessEngine;
use function Makasim\Yadm\unset_object_id;
use Psr\Log\NullLogger;

class ScheduleJobProcessor implements PsrProcessor, TopicSubscriberInterface
{
    /**
     * @var ProcessEngine
     */
    private $processEngine;

    /**
     * @var ProcessStorage
     */
    private $processStorage;

    /**
     * @var ProcessExecutionStorage
     */
    private $processExecutionStorage;

    /**
     * @param ProcessEngine $processEngine
     * @param ProcessStorage $processStorage
     * @param ProcessExecutionStorage $processExecutionStorage
     */
    public function __construct(
        ProcessEngine $processEngine,
        ProcessStorage $processStorage,
        ProcessExecutionStorage $processExecutionStorage
    ) {
        $this->processEngine = $processEngine;
        $this->processStorage = $processStorage;
        $this->processExecutionStorage = $processExecutionStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $psrMessage, PsrContext $psrContext)
    {
        $processId = $psrMessage->getBody();

        /** @var Process $process */
        if (false == $process = $this->processExecutionStorage->findOne(['id' => $processId])) {
            /** @var Process $process */
            if (false == $process = $this->processStorage->findOne(['id' => $processId])) {
                return self::REJECT;
            }

            unset_object_id($process);
            $this->processExecutionStorage->insert($process);
        }

        foreach ($process->getTransitions() as $transition) {
            if ($transition->getFrom() === null) {
                break;
            }
        }

        try {
            $token = $process->createToken($transition);

            $waitTokens = $this->processEngine->proceed($token, new NullLogger());
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
        return [Topics::SCHEDULE_JOB];
    }
}
