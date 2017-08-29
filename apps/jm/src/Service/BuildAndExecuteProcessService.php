<?php
namespace App\Service;

use App\Infra\Uuid;
use App\JobStatus;
use App\Model\Job;
use App\Model\JobResult;
use App\Model\Process;
use App\Storage\JobStorage;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessExecutionStorage;
use Formapro\Pvm\ProcessEngine;
use function Makasim\Values\build_object;
use function Makasim\Values\get_values;
use function Makasim\Values\set_value;
use function Makasim\Yadm\unset_object_id;

class BuildAndExecuteProcessService
{
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
     * @var JobTemplateStorage
     */
    private $jobTemplateStorage;

    /**
     * @param ProcessExecutionStorage $processExecutionStorage
     * @param ProcessEngine $processEngine
     * @param JobStorage $jobStorage
     * @param JobTemplateStorage $jobTemplateStorage
     */
    public function __construct(
        ProcessExecutionStorage $processExecutionStorage,
        ProcessEngine $processEngine,
        JobStorage $jobStorage,
        JobTemplateStorage $jobTemplateStorage
    ) {
        $this->processExecutionStorage = $processExecutionStorage;
        $this->jobStorage = $jobStorage;
        $this->jobTemplateStorage = $jobTemplateStorage;
        $this->processEngine = $processEngine;
    }

    public function buildAndRun(Process $templateProcess):Process
    {
        return $this->run($this->build($templateProcess));
    }

    public function build(Process $templateProcess):Process
    {
        /** @var Process $process */
        $process = build_object(Process::class, get_values($templateProcess));

        unset_object_id($process);
        set_value($process, 'templateId', $process->getId());
        $process->setId(Uuid::generate());
        $this->processExecutionStorage->insert($process);

        foreach ($process->getJobTemplateIds() as $jobTemplateId) {
            $jobTemplate = $this->jobTemplateStorage->findOne(['templateId' => $jobTemplateId]);

            $job = Job::createFromTemplate($jobTemplate);
            $job->setId(Uuid::generate());
            $job->setCreatedAt(new \DateTime('now'));

            $this->jobStorage->insert($job);

            $process->map($job->getTemplateId(), $job->getId());
        }
        $this->processExecutionStorage->update($process);

        foreach ($process->getJobIds() as $jobId) {
            if (false == $job = $this->jobStorage->findOne(['id' => $jobId])) {
                throw new \LogicException(sprintf('The job with id "%s" could not be found', $jobId));
            }

            $job->setProcessId($process->getId());

            $result = JobResult::createFor(JobStatus::STATUS_NEW);
            $job->addResult($result);
            $job->setCurrentResult($result);

            $this->jobStorage->update($job);
        }
        $this->processExecutionStorage->update($process);

        return $process;
    }

    public function run(Process $process):Process
    {
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

        return $process;
    }
}
