<?php
namespace App\Pvm\Behavior;

use App\Async\Commands;
use App\JobStatus;
use App\Model\Job;
use App\Model\JobResult;
use App\Model\Process;
use App\Service\CreateProcessForSubJobsService;
use App\Storage\JobStorage;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessStorage;
use Enqueue\Client\ProducerV2Interface;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Exception\WaitExecutionException;
use Formapro\Pvm\SignalBehavior;
use Formapro\Pvm\Token;

class RunSubJobsProcessBehavior implements Behavior, SignalBehavior
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var JobTemplateStorage
     */
    private $jobTemplateStorage;

    /**
     * @var ProcessStorage
     */
    private $processStorage;

    /**
     * @var CreateProcessForSubJobsService
     */
    private $createProcessForSubJobsService;

    /**
     * @var ProducerV2Interface
     */
    private $producer;

    /**
     * @param JobStorage $jobStorage
     * @param JobTemplateStorage $jobTemplateStorage
     * @param ProcessStorage $processStorage
     * @param CreateProcessForSubJobsService $createProcessForSubJobsService
     * @param ProducerV2Interface $producer
     */
    public function __construct(
        JobStorage $jobStorage,
        JobTemplateStorage $jobTemplateStorage,
        ProcessStorage $processStorage,
        CreateProcessForSubJobsService $createProcessForSubJobsService,
        ProducerV2Interface $producer
    ) {
        $this->jobStorage = $jobStorage;
        $this->jobTemplateStorage = $jobTemplateStorage;
        $this->processStorage = $processStorage;
        $this->createProcessForSubJobsService = $createProcessForSubJobsService;
        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
        /** @var Process $process */
        $process = $token->getProcess();
        $job = $this->jobStorage->getOneById($process->getTokenJobId($token));

        if (false == $job->getCurrentResult()->isRunSubJobs()) {
            throw new \LogicException('The process comes to this task but its status is not "run_sub_jobs"');
        }

        $jobTemplates = iterator_to_array($this->jobTemplateStorage->find(['parentId' => $job->getId()]));
        if (false == $jobTemplates) {
            $jobResult = JobResult::createFor(JobStatus::STATUS_COMPLETED);
            $job->addResult($jobResult);
            $job->setCurrentResult($jobResult);
            $this->jobStorage->update($job);

            return;
        }

        $jobResult = JobResult::createFor(JobStatus::STATUS_RUNNING_SUB_JOBS);
        $job->addResult($jobResult);
        $job->setCurrentResult($jobResult);
        $this->jobStorage->update($job);

        $subProcess = $this->createProcessForSubJobsService->createProcess($token, $jobTemplates);
        $this->processStorage->insert($subProcess);

        $this->producer->sendCommand(Commands::SCHEDULE_PROCESS, $subProcess->getId());

        throw new WaitExecutionException();
    }

    /**
     * {@inheritdoc}
     */
    public function signal(Token $token)
    {
        /** @var Process $process */
        $process = $token->getProcess();

        return $this->jobStorage->lockByJobId($process->getTokenJobId($token), function(Job $job) {
            if ($job->getRunSubJobsPolicy()->isMarkParentJobAsFailed()) {
                foreach ($this->jobStorage->findSubJobs($job->getId()) as $subJob) {
                    if ($subJob->getCurrentResult()->isFailed()) {
                        $jobResult = JobResult::createFor(JobStatus::STATUS_FAILED);
                        $job->addResult($jobResult);
                        $job->setCurrentResult($jobResult);

                        $this->jobStorage->update($job);

                        return ['failed'];
                    }
                }
            }

            $jobResult = JobResult::createFor(JobStatus::STATUS_COMPLETED);
            $job->addResult($jobResult);
            $job->setCurrentResult($jobResult);

            $this->jobStorage->update($job);

            return ['completed'];
        });
    }
}
