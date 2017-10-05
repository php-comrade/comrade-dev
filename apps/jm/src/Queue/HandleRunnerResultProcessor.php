<?php
namespace App\Queue;

use App\Commands;
use App\Model\JobResult;
use App\Storage\TriggerStorage;
use Comrade\Shared\Message\RunnerResult;
use App\Infra\JsonSchema\Errors;
use App\Infra\JsonSchema\SchemaValidator;
use App\Storage\JobStorage;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessExecutionStorage;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Consumption\QueueSubscriberInterface;
use Enqueue\Consumption\Result;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Enqueue\Util\JSON;

class HandleRunnerResultProcessor implements PsrProcessor, CommandSubscriberInterface, QueueSubscriberInterface
{
    /**
     * @var SchemaValidator
     */
    private $schemaValidator;

    /**
     * @var ProcessExecutionStorage
     */
    private $processExecutionStorage;

    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var JobTemplateStorage
     */
    private $jobTemplateStorage;

    /**
     * @var TriggerStorage
     */
    private $triggerStorage;

    /**
     * @var ProducerInterface
     */
    private $producer;

    public function __construct(
        SchemaValidator $schemaValidator,
        ProcessExecutionStorage $processExecutionStorage,
        JobStorage $jobStorage,
        JobTemplateStorage $jobTemplateStorage,
        TriggerStorage $triggerStorage,
        ProducerInterface $producer
    ) {
        $this->schemaValidator = $schemaValidator;
        $this->processExecutionStorage = $processExecutionStorage;
        $this->jobStorage = $jobStorage;
        $this->jobTemplateStorage = $jobTemplateStorage;
        $this->producer = $producer;
        $this->triggerStorage = $triggerStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $psrMessage, PsrContext $psrContext)
    {
        $data = JSON::decode($psrMessage->getBody());
        if ($errors = $this->schemaValidator->validate($data, RunnerResult::SCHEMA)) {
            return Result::reject(Errors::toString($errors, 'Message schema validation has failed.'));
        }

        $runnerResult = RunnerResult::create($data);

        if (false == $process = $this->processExecutionStorage->getOneByToken($runnerResult->getToken())) {
            return Result::reject(sprintf('The process assoc with the token "%s" could not be found', $runnerResult->getToken()));
        }

        $token = $process->getToken($runnerResult->getToken());
        $token->setRunnerResult($runnerResult);

        $this->processExecutionStorage->update($process);

        $this->producer->sendCommand(Commands::PVM_HANDLE_ASYNC_TRANSITION, [
            'process' => $process->getId(),
            'token' => $token->getId(),
        ]);

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedCommand()
    {
        return [
            'processorName' => Commands::HANDLE_RUNNER_RESULT,
            'queueName' => Commands::HANDLE_RUNNER_RESULT,
            'queueNameHardcoded' => true,
            'exclusive' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedQueues()
    {
        return [Commands::HANDLE_RUNNER_RESULT];
    }
}
