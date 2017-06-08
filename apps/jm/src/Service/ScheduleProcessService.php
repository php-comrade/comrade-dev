<?php
namespace App\Service;

use App\Infra\Uuid;
use App\Model\Job;
use App\Model\JobResult;
use App\Model\Process;
use App\Storage\JobStorage;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessExecutionStorage;
use function Makasim\Values\build_object;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_value;
use function Makasim\Yadm\unset_object_id;

class ScheduleProcessService
{
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
     * @param ProcessExecutionStorage $processExecutionStorage
     * @param JobStorage $jobStorage
     * @param JobTemplateStorage $jobTemplateStorage
     */
    public function __construct(
        ProcessExecutionStorage $processExecutionStorage,
        JobStorage $jobStorage,
        JobTemplateStorage $jobTemplateStorage
    ) {
        $this->processExecutionStorage = $processExecutionStorage;
        $this->jobStorage = $jobStorage;
        $this->jobTemplateStorage = $jobTemplateStorage;
    }

    public function schedule(Process $templateProcess):Process
    {
        /** @var Process $process */
        $process = build_object(Process::class, get_values($templateProcess));

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

        return $process;
    }
}