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
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Enqueue\Util\JSON;

class HandleRunnerResultProcessor implements Processor, CommandSubscriberInterface, QueueSubscriberInterface
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
    public function process(Message $Message, Context $Context)
    {
        $data = JSON::decode($Message->getBody());
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
            'token' => $token->getId(),
        ]);

        return self::ACK;
    }

    public static function getSubscribedCommand(): array
    {
        return [
            'command' => Commands::HANDLE_RUNNER_RESULT,
            'queue' => Commands::HANDLE_RUNNER_RESULT,
            'prefix_queue' => false,
            'exclusive' => true,
        ];
    }

    public static function getSubscribedQueues(): array
    {
        return [Commands::HANDLE_RUNNER_RESULT];
    }
}
