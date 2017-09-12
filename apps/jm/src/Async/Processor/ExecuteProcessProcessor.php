<?php
namespace App\Async\Processor;

use App\Async\Commands;
use App\Service\BuildAndExecuteProcessService;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessStorage;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Consumption\Result;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Enqueue\Util\JSON;

class ExecuteProcessProcessor implements PsrProcessor, CommandSubscriberInterface
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
        $data = JSON::decode($psrMessage->getBody());

        if (false == array_key_exists('processTemplateId', $data)) {
            return Result::reject('The message does not contain required field "processTemplateId"');
        }

        if (false == $processTemplate = $this->processStorage->findOne(['id' => $data['processTemplateId']])) {
            return Result::reject(sprintf('The process template with id "%s" could not be found', $data['processTemplateId']));
        }

        $this->buildAndExecuteProcessService->buildAndRun($processTemplate);

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedCommand()
    {
        return Commands::EXECUTE_PROCESS;
    }
}
