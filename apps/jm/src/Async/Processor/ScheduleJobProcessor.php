<?php
namespace App\Async\Processor;

use App\Async\Topics;
use App\Infra\Uuid;
use App\Model\Job;
use App\Model\JobResult;
use App\Model\Process;
use App\Storage\JobStorage;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessExecutionStorage;
use App\Storage\ProcessStorage;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;
use Formapro\Pvm\ProcessEngine;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;
use function Makasim\Yadm\unset_object_id;
use Psr\Log\NullLogger;

class ScheduleJobProcessor implements PsrProcessor, TopicSubscriberInterface
{
    /**
     * @var ProcessEngine
     */
    private $processEngine;

    /**
     * @var ProcessStorage
     */
    private $processStorage;

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
     * @param ProcessEngine $processEngine
     * @param ProcessStorage $processStorage
     * @param ProcessExecutionStorage $processExecutionStorage
     * @param JobStorage $jobStorage
     * @param JobTemplateStorage $jobTemplateStorage
     */
    public function __construct(
        ProcessEngine $processEngine,
        ProcessStorage $processStorage,
        ProcessExecutionStorage $processExecutionStorage,
        JobStorage $jobStorage,
        JobTemplateStorage $jobTemplateStorage
    ) {
        $this->processEngine = $processEngine;
        $this->processStorage = $processStorage;
        $this->processExecutionStorage = $processExecutionStorage;
        $this->jobStorage = $jobStorage;
        $this->jobTemplateStorage = $jobTemplateStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $psrMessage, PsrContext $psrContext)
    {
        if ($psrMessage->isRedelivered()) {
            return Result::reject('The message failed. Remove it');
        }

        $processId = $psrMessage->getBody();

        /** @var Process $process */
        if (false == $process = $this->processExecutionStorage->findOne(['id' => $processId])) {
            /** @var Process $process */
            if (false == $process = $this->processStorage->findOne(['id' => $processId])) {
                return self::REJECT;
            }

            unset_object_id($process);
            set_value($process, 'templateId', $process->getId());
            $process->setId(Uuid::generate());


            foreach (get_value($process, 'jobTemplateIds') as $jobTemplateId) {
                $jobTemplate = $this->jobTemplateStorage->findOne(['templateId' => $jobTemplateId]);

                $job = Job::createFromTemplate($jobTemplate);
                $job->setId(Uuid::generate());
                $job->setProcessId($process->getId());

                $result = JobResult::createFor(Job::STATUS_NEW);
                $job->addResult($result);
                $job->setCurrentResult($result);

                $process->addJob($job);

                $this->jobStorage->insert($job);
            }

            $this->processExecutionStorage->insert($process);
        }

        try {
            foreach ($process->getTransitions() as $transition) {
                if ($transition->getFrom() === null) {
                    $token = $process->createToken($transition);

                    $this->processEngine->proceed($token);
                }
            }
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
        return [Topics::SCHEDULE_JOB];
    }
}
