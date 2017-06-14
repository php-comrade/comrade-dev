<?php
namespace App\Async\Processor;

use App\Async\Commands;
use App\Service\ScheduleJobService;
use App\Service\BuildAndExecuteProcessService;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessStorage;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;
use Enqueue\Util\JSON;

class ExecuteJobProcessor implements PsrProcessor, CommandSubscriberInterface
{
    /**
     * @var JobTemplateStorage
     */
    private $jobTemplateStorage;

    /**
     * @var ProcessStorage
     */
    private $processStorage;

    /**
     * @var BuildAndExecuteProcessService
     */
    private $buildAndExecuteProcessService;

    /**
     * @param JobTemplateStorage $jobTemplateStorage
     * @param ProcessStorage $processStorage
     * @param BuildAndExecuteProcessService $buildAndExecuteProcessService
     */
    public function __construct(
        JobTemplateStorage $jobTemplateStorage,
        ProcessStorage $processStorage,
        BuildAndExecuteProcessService $buildAndExecuteProcessService
    ) {
        $this->jobTemplateStorage = $jobTemplateStorage;
        $this->processStorage = $processStorage;
        $this->buildAndExecuteProcessService = $buildAndExecuteProcessService;
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
        if (false == $jobTemplate = $this->jobTemplateStorage->findOne(['id' => $data['jobTemplate']])) {
            return self::REJECT;
        }

        if (false == $processTemplate = $this->processStorage->findOne(['templateId' => $jobTemplate->getProcessTemplateId()])) {
            return self::REJECT;
        }

        $this->buildAndExecuteProcessService->buildAndRun($processTemplate);

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedCommand()
    {
        return Commands::EXECUTE_JOB;
    }
}
