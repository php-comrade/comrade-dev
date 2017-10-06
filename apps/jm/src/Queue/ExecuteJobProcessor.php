<?php
namespace App\Queue;

use App\Commands;
use App\Infra\JsonSchema\Errors;
use App\Infra\JsonSchema\SchemaValidator;
use App\Message\ExecuteJob;
use App\Service\BuildAndExecuteProcessService;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessStorage;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Consumption\Result;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Enqueue\Util\JSON;

class ExecuteJobProcessor implements PsrProcessor, CommandSubscriberInterface
{
    /**
     * @var SchemaValidator
     */
    private $schemaValidator;

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

    public function __construct(
        SchemaValidator $schemaValidator,
        JobTemplateStorage $jobTemplateStorage,
        ProcessStorage $processStorage,
        BuildAndExecuteProcessService $buildAndExecuteProcessService
    ) {
        $this->schemaValidator = $schemaValidator;
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
        if ($errors = $this->schemaValidator->validate($data, ExecuteJob::SCHEMA)) {
            return Result::reject(Errors::toString($errors, 'Message schema validation has failed.'));
        }

        $executeJob = ExecuteJob::create($data);
        $trigger = $executeJob->getTrigger();

        if (false == $jobTemplate = $this->jobTemplateStorage->findOne(['templateId' => $trigger->getTemplateId()])) {
            return self::REJECT;
        }

        if (false == $processTemplate = $this->processStorage->findOne(['id' => $jobTemplate->getProcessTemplateId()])) {
            return Result::reject(sprintf('The process template with id "%s" could not be found', $data['processTemplateId']));
        }

        $this->buildAndExecuteProcessService->buildAndRun($processTemplate, $trigger);

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
