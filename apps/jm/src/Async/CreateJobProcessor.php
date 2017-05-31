<?php
namespace App\Async;

use App\Infra\JsonSchema\Errors;
use App\Infra\JsonSchema\SchemaValidator;
use App\Storage\JobStorage\JobStorage;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;
use Enqueue\Util\JSON;

class CreateJobProcessor implements PsrProcessor, TopicSubscriberInterface
{
    /**
     * @var SchemaValidator
     */
    private $schemaValidator;

    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @param SchemaValidator $schemaValidator
     * @param JobStorage $jobStorage
     */
    public function __construct(SchemaValidator $schemaValidator, JobStorage $jobStorage)
    {
        $this->schemaValidator = $schemaValidator;
        $this->jobStorage = $jobStorage;
    }


    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $psrMessage, PsrContext $psrContext)
    {
        $data = JSON::decode($psrMessage->getBody());
        if ($errors = $this->schemaValidator->validate($data, CreateJob::SCHEMA)) {
            return Result::reject(Errors::toString($errors, 'Message schema validation has failed.'));
        }

        $message = CreateJob::create($data);

        $this->jobStorage->insert($message->getJob());

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::CREATE_JOB];
    }
}