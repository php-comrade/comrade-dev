<?php
namespace App\Async\Processor;

use App\Async\JobResult;
use App\Async\Topics;
use App\Infra\JsonSchema\Errors;
use App\Infra\JsonSchema\SchemaValidator;
use App\Model\Job;
use App\Storage\JobStorage;
use App\Storage\ProcessExecutionStorage;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;
use Enqueue\Util\JSON;
use Formapro\Pvm\ProcessEngine;
use function Makasim\Values\add_object;
use function Makasim\Values\set_object;
use function Makasim\Yadm\get_object_id;
use Psr\Log\NullLogger;

class FeedbackJobProcessor implements PsrProcessor, TopicSubscriberInterface
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
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @param SchemaValidator $schemaValidator
     * @param ProcessExecutionStorage $processExecutionStorage
     * @param ProcessEngine $processEngine
     * @param JobStorage $jobStorage
     */
    public function __construct(
        SchemaValidator $schemaValidator,
        ProcessExecutionStorage $processExecutionStorage,
        ProcessEngine $processEngine,
        JobStorage $jobStorage
    ) {
        $this->schemaValidator = $schemaValidator;
        $this->processExecutionStorage = $processExecutionStorage;
        $this->processEngine = $processEngine;
        $this->jobStorage = $jobStorage;
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
        if ($errors = $this->schemaValidator->validate($data, JobResult::SCHEMA)) {
            return Result::reject(Errors::toString($errors, 'Message schema validation has failed.'));
        }

        $message = JobResult::create($data);
        $token = $message->getToken();

        if (false == $process = $this->processExecutionStorage->getOneByToken($message->getToken())) {
            return self::REJECT;
        }

        $job = $this->jobStorage->getOneById($message->getJobId());
        $this->jobStorage->lock(get_object_id($job), function(Job $job, JobStorage $jobStorage) use($message) {
            $job->addResult($message->getResult());
            $job->setCurrentResult($message->getResult());

            $jobStorage->update($job);
        });

        try {
            $token = $process->getToken($token);
            $this->processEngine->proceed($token);
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
        return [Topics::PROCESS_FEEDBACK => ['processorName' => 'job_manager_process_feedback']];
    }
}
