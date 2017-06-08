<?php
namespace App\Async\Processor;

use App\Async\JobResult;
use App\Async\Topics;
use App\Async\RunSubJobsResult;
use App\Infra\JsonSchema\Errors;
use App\Infra\JsonSchema\SchemaValidator;
use App\Model\Job;
use App\Model\SubJobTemplate;
use App\Service\CreateProcessForSubJobsService;
use App\Storage\JobStorage;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessExecutionStorage;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;
use Enqueue\Util\JSON;
use Formapro\Pvm\ProcessEngine;
use function Makasim\Values\add_object;
use function Makasim\Values\build_object;
use function Makasim\Values\set_object;
use function Makasim\Values\set_value;
use function Makasim\Yadm\get_object_id;
use Psr\Log\NullLogger;

class JobResultProcessor implements PsrProcessor, TopicSubscriberInterface
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
     * @var CreateProcessForSubJobsService
     */
    private $createProcessForSubJobsService;
    /**
     * @var JobTemplateStorage
     */
    private $jobTemplateStorage;

    /**
     * @param SchemaValidator $schemaValidator
     * @param ProcessExecutionStorage $processExecutionStorage
     * @param ProcessEngine $processEngine
     * @param JobStorage $jobStorage
     * @param JobTemplateStorage $jobTemplateStorage
     * @param CreateProcessForSubJobsService $createProcessForSubJobsService
     */
    public function __construct(
        SchemaValidator $schemaValidator,
        ProcessExecutionStorage $processExecutionStorage,
        ProcessEngine $processEngine,
        JobStorage $jobStorage,
        JobTemplateStorage $jobTemplateStorage,
        CreateProcessForSubJobsService $createProcessForSubJobsService
    ) {
        $this->schemaValidator = $schemaValidator;
        $this->processExecutionStorage = $processExecutionStorage;
        $this->processEngine = $processEngine;
        $this->jobStorage = $jobStorage;
        $this->jobTemplateStorage = $jobTemplateStorage;
        $this->createProcessForSubJobsService = $createProcessForSubJobsService;
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

        if (
            RunSubJobsResult::SCHEMA == $data['schema'] &&
            $errors = $this->schemaValidator->validate($data, RunSubJobsResult::SCHEMA)
        ) {
            return Result::reject(Errors::toString($errors, 'Message schema validation has failed.'));
        }



        $message = JobResult::create($data);
        $token = $message->getToken();

        if (false == $process = $this->processExecutionStorage->getOneByToken($message->getToken())) {
            return self::REJECT;
        }

        $this->jobStorage->lockByJobId($message->getJobId(), function(Job $job) use($message) {
            $job->addResult($message->getResult());
            $job->setCurrentResult($message->getResult());

            if ($message instanceof RunSubJobsResult) {
                $jobTemplates = iterator_to_array($message->getJobTemplates());
                foreach ($jobTemplates as $jobTemplate) {
                    $jobTemplate = SubJobTemplate::createFromJobTemplate($job->getId(), $jobTemplate);

                    $this->jobTemplateStorage->insert($jobTemplate);
                }
            }

            $this->jobStorage->update($job);
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
        return [Topics::JOB_RESULT => ['processorName' => 'job_result']];
    }
}
