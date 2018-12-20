<?php
namespace App;

use App\Infra\Pvm\StateMachine;
use App\Model\JobAction;
use App\Model\JobTemplate;
use App\Model\PvmProcess;
use Comrade\Shared\Model\Job;
use Comrade\Shared\Model\JobStatus;
use Formapro\Pvm\Node;
use Formapro\Pvm\ProcessBuilder;
use Formapro\Pvm\Transition;

class JobStateMachine
{
    /**
     * @var PvmProcess
     */
    private $process;

    /**
     * @var ProcessBuilder
     */
    private $processBuilder;

    /**
     * @var JobTemplate|Job
     */
    private $job;

    public function __construct(\Comrade\Shared\Model\JobTemplate $job)
    {
        $this->process = $process = new PvmProcess();
        $this->processBuilder = new ProcessBuilder($process);
        $this->job = $job;

        $new = $this->createState(JobStatus::NEW);
        $completed = $this->createState(JobStatus::COMPLETED);
        $failed = $this->createState(JobStatus::FAILED);
        $terminated = $this->createState(JobStatus::TERMINATED);
        $canceled = $this->createState(JobStatus::CANCELED);
        $running = $this->createState(JobStatus::RUNNING);



        $this->processBuilder->createTransition($new, $running, JobAction::RUN);
        $this->processBuilder->createTransition($running, $completed, JobAction::COMPLETE);
        $this->processBuilder->createTransition($running, $failed, JobAction::FAIL);
        $this->processBuilder->createTransition($running, $canceled, JobAction::CANCEL);
        $this->processBuilder->createTransition($running, $terminated, JobAction::TERMINATE);

        if ($job->getRetryFailedPolicy()) {
            $retrying = $this->createState(JobStatus::RETRYING);

            $this->processBuilder->createTransition($failed, $retrying, JobAction::RETRY);
            $this->processBuilder->createTransition($retrying, $running, JobAction::RUN);
            $this->processBuilder->createTransition($retrying, $failed, JobAction::FAIL);
        }

        if ($job->getRunSubJobsPolicy()) {
            $runningSubJobs = $this->createState(JobStatus::RUNNING_SUB_JOBS);

            $this->processBuilder->createTransition($running, $runningSubJobs, JobAction::RUN_SUB_JOBS);
            $this->processBuilder->createTransition($runningSubJobs, $completed, JobAction::COMPLETE);
            $this->processBuilder->createTransition($runningSubJobs, $failed, JobAction::FAIL);
            $this->processBuilder->createTransition($runningSubJobs, $canceled, JobAction::CANCEL);
            $this->processBuilder->createTransition($runningSubJobs, $terminated, JobAction::TERMINATE);
        }

        if ($job->getSubJobPolicy()) {
            $this->processBuilder->createTransition($new, $terminated, JobAction::TERMINATE);
        }

        if ($exclusivePolicy = $job->getExclusivePolicy()) {
            if ($exclusivePolicy->isMarkJobAsCanceledOnDuplicateRun()) {
                $this->processBuilder->createTransition($new, $canceled, 'terminate_on_duplicate');
            } elseif ($exclusivePolicy->isMarkJobAsFailedOnDuplicateRun()) {
                $this->processBuilder->createTransition($new, $failed, 'terminate_on_duplicate');
            } else {
                throw new \LogicException(sprintf('The exclusive policy action "%s" is not supported', $exclusivePolicy->getOnDuplicateRun()));
            }
        }

        return $this->processBuilder->getProcess();
    }

    public function can(string $action): ?Transition
    {
        if (false == $this->job instanceof Job) {
            throw new \LogicException('The method can be called for an instance of Job only.');
        }

        $sm = new StateMachine($this->getProcess());

        return $sm->can($this->job->getCurrentResult()->getStatus(), $action);
    }

    public function getProcess(): PvmProcess
    {
        return $this->processBuilder->getProcess();
    }

    private function createState(string $state): Node
    {
        return $this->processBuilder->createNode()
            ->setLabel($state)
            ->getNode()
        ;
    }
}
