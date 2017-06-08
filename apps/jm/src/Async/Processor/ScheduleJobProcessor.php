<?php
namespace App\Async\Processor;

use App\Async\Topics;
use App\Service\ScheduleProcessService;
use App\Storage\ProcessExecutionStorage;
use App\Storage\ProcessStorage;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;
use Formapro\Pvm\ProcessEngine;

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
     * @var ScheduleProcessService
     */
    private $scheduleProcessService;

    /**
     * @param ProcessEngine $processEngine
     * @param ProcessStorage $processStorage
     * @param ProcessExecutionStorage $processExecutionStorage
     * @param ScheduleProcessService $scheduleProcessService
     */
    public function __construct(
        ProcessEngine $processEngine,
        ProcessStorage $processStorage,
        ProcessExecutionStorage $processExecutionStorage,
        ScheduleProcessService $scheduleProcessService
    ) {
        $this->processEngine = $processEngine;
        $this->processStorage = $processStorage;
        $this->processExecutionStorage = $processExecutionStorage;
        $this->scheduleProcessService = $scheduleProcessService;
    }

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $psrMessage, PsrContext $psrContext)
    {
        if ($psrMessage->isRedelivered()) {
            return Result::reject('The message failed. Remove it');
        }

        $processId = $psrMessage->getBody();
        if (false == $templateProcess = $this->processStorage->findOne(['id' => $processId])) {
            return self::REJECT;
        }

        $process = $this->scheduleProcessService->schedule($templateProcess);

        try {
            foreach ($process->getTransitions() as $transition) {
                if ($transition->getFrom() === null) {
                    $token = $process->createToken($transition);

                    $this->processEngine->proceed($token);
                }
            }
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
        return [Topics::SCHEDULE_PROCESS];
    }
}
