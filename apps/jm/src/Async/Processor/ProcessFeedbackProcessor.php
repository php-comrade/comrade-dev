<?php
namespace App\Async\Processor;

use App\Async\ProcessFeedback;
use App\Async\Topics;
use App\Infra\JsonSchema\Errors;
use App\Infra\JsonSchema\SchemaValidator;
use App\Storage\ProcessExecutionStorage;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;
use Enqueue\Util\JSON;
use Formapro\Pvm\ProcessEngine;
use function Makasim\Values\set_object;
use function Makasim\Values\set_value;
use Psr\Log\NullLogger;

class ProcessFeedbackProcessor implements PsrProcessor, TopicSubscriberInterface
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
     * @var ProcessEngine
     */
    private $processEngine;

    /**
     * @param SchemaValidator $schemaValidator
     * @param ProcessExecutionStorage $processExecutionStorage
     * @param ProcessEngine $processEngine
     */
    public function __construct(
        SchemaValidator $schemaValidator,
        ProcessExecutionStorage $processExecutionStorage,
        ProcessEngine $processEngine
    ) {
        $this->schemaValidator = $schemaValidator;
        $this->processExecutionStorage = $processExecutionStorage;
        $this->processEngine = $processEngine;
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
        if ($errors = $this->schemaValidator->validate($data, ProcessFeedback::SCHEMA)) {
            return Result::reject(Errors::toString($errors, 'Message schema validation has failed.'));
        }

        $message = ProcessFeedback::create($data);
        $job = $message->getJob();
        $token = $message->getToken();

        if (false == $process = $this->processExecutionStorage->findOne(['jobs.uid' => $job->getUid()])) {
            return self::REJECT;
        }

        try {
            $token = $process->getToken($token);

            set_object($token->getTransition()->getTo(), 'jobFeedback', $message->getJobFeedback());

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
        return [
            Topics::PROCESS_FEEDBACK => [
                'queueName' => 'job_manager_process_feedback',
                'queueNameHardcoded' => true,
                'processorName' => 'job_manager_process_feedback',
            ]
        ];
    }
}
