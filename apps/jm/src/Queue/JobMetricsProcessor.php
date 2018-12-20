<?php

namespace App\Queue;

use App\Topics;
use App\Infra\JsonSchema\Errors;
use App\Infra\JsonSchema\SchemaValidator;
use App\JobStatus;
use App\Storage\JobMetricsStorage;
use Comrade\Shared\Model\Job;
use Comrade\Shared\Model\JobMetrics;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Util\JSON;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;

class JobMetricsProcessor implements Processor, TopicSubscriberInterface
{
    /**
     * @var JobMetricsStorage
     */
    private $metricsStorage;

    /**
     * @var SchemaValidator
     */
    private $schemaValidator;

    public function __construct(JobMetricsStorage $metricsStorage, SchemaValidator $schemaValidator)
    {
        $this->metricsStorage = $metricsStorage;
        $this->schemaValidator = $schemaValidator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Message $message, Context $context)
    {
        if ($message->isRedelivered()) {
            return Result::reject('Message is redelivered. Reject it');
        }

        $data = JSON::decode($message->getBody());

        if ($errors = $this->schemaValidator->validate($data, Job::SCHEMA)) {
            return Result::reject(Errors::toString($errors, 'Message schema validation has failed.'));
        }

        $job = Job::create($data);

        if (false == $jobResultMetrics = $job->getCurrentResult()->getMetrics()) {
            return Result::ACK;
        }

        if (get_value($job, 'finishedAt', null, \DateTime::class)) {
            return Result::ack(sprintf(
                'The job status "%s" is not one of the done statuses (%s). Metrics are not calculated for intermediate statuses. Ignoring.',
                $job->getCurrentResult()->getStatus(),
                implode(', ', JobStatus::getDoneStatuses())
            ));
        }

        $scheduledTime = get_value($job, 'startAt', null, \DateTime::class);
        if (false == $scheduledTime) {
            throw new \LogicException(sprintf('The job "%s" has done status but there is no running one which is exceptional case.', $job->getId()));
        }

        $metrics = new JobMetrics();
        $metrics->setTemplateId($job->getTemplateId());
        $metrics->setJobId($job->getId());
        $metrics->setStatus($job->getCurrentResult()->getStatus());
        $metrics->setDuration($jobResultMetrics->getDuration());
        $metrics->setMemory($jobResultMetrics->getMemory());
        $metrics->setScheduledTime($scheduledTime);
        $metrics->setStartTime(\DateTime::createFromFormat('U', $jobResultMetrics->getStartTime()/1000));

        $waitTimeSec = ((int) $metrics->getStartTime()->format('U')) - ((int) $metrics->getScheduledTime()->format('U'));
        $metrics->setWaitTime($waitTimeSec * 1000);

        $this->metricsStorage->insert($metrics);

        return Result::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::JOB_UPDATED];
    }
}