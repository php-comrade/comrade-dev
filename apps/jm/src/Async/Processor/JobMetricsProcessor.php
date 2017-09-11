<?php

namespace App\Async\Processor;

use App\Async\Topics;
use App\Infra\JsonSchema\Errors;
use App\Infra\JsonSchema\SchemaValidator;
use App\JobStatus;
use App\Model\Job;
use App\Model\JobMetrics;
use App\Storage\JobMetricsStorage;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Util\JSON;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class JobMetricsProcessor implements PsrProcessor, TopicSubscriberInterface
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
    public function process(PsrMessage $message, PsrContext $context)
    {
        $data = JSON::decode($message->getBody());

        if ($errors = $this->schemaValidator->validate($data, Job::SCHEMA)) {
            return Result::reject(Errors::toString($errors, 'Message schema validation has failed.'));
        }

        $job = Job::create($data);

        if (false == JobStatus::isDone($job->getCurrentResult())) {
            return Result::ack(sprintf(
                'The job status "%s" is not one of the done statuses (%s). Metrics are not calculated for intermediate statuses. Ignoring.',
                $job->getCurrentResult()->getStatus(),
                implode(', ', JobStatus::getDoneStatuses())
            ));
        }

        $scheduledTime = null;
        foreach ($job->getResults() as $result) {
            if (JobStatus::isNew($result)) {
                $scheduledTime = $result->getCreatedAt();
                break;
            }
        }

        if (false == $scheduledTime) {
            throw new \LogicException(sprintf('The job "%s" has done status but there is no running one which is exceptional case.', $job->getId()));
        }

        $metrics = new JobMetrics();
        $metrics->setTemplateId($job->getTemplateId());
        $metrics->setJobId($job->getId());
        $metrics->setStatus($job->getCurrentResult()->getStatus());
        $metrics->setDuration($job->getCurrentResult()->getDuration());
        $metrics->setMemory($job->getCurrentResult()->getMemory());
        $metrics->setScheduledTime($scheduledTime);
        $metrics->setStartTime(\DateTime::createFromFormat('U', $job->getCurrentResult()->getStartTime()/1000));

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
        return [Topics::UPDATE_JOB];
    }
}