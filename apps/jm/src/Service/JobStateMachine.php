<?php
namespace App\Service;

use App\Infra\Pvm\StateMachine;
use App\Model\JobAction;
use App\Model\JobTemplate;
use App\Model\PvmProcess;
use Comrade\Shared\Model\Job;
use Comrade\Shared\Model\JobStatus;
use Formapro\Pvm\Node;
use Formapro\Pvm\Transition;

class JobStateMachine
{
    /**
     * @var PvmProcess
     */
    private $process;

    /**
     * @var JobTemplate|Job
     */
    private $job;

    public function __construct(\Comrade\Shared\Model\JobTemplate $job)
    {
        $this->process = $process = new PvmProcess();
        $this->job = $job;

        $new = $this->createState(JobStatus::NEW);
        $completed = $this->createState(JobStatus::COMPLETED);
        $failed = $this->createState(JobStatus::FAILED);
        $terminated = $this->createState(JobStatus::TERMINATED);
        $canceled = $this->createState(JobStatus::CANCELED);
        $running = $this->createState(JobStatus::RUNNING);

        $process->createTransition($new, $running, JobAction::RUN);
        $process->createTransition($running, $completed, JobAction::COMPLETE);
        $process->createTransition($running, $failed, JobAction::FAIL);
        $process->createTransition($running, $canceled, JobAction::CANCEL);
        $process->createTransition($running, $terminated, JobAction::TERMINATE);

        if ($job->getRetryFailedPolicy()) {
            $retrying = $this->createState(JobStatus::RETRYING);

            $process->createTransition($failed, $retrying, JobAction::RETRY);
            $process->createTransition($retrying, $running, JobAction::RUN);
            $process->createTransition($retrying, $failed, JobAction::FAIL);
        }

        if ($job->getRunSubJobsPolicy()) {
            $runningSubJobs = $this->createState(JobStatus::RUNNING_SUB_JOBS);

            $process->createTransition($running, $runningSubJobs, JobAction::RUN_SUB_JOBS);
            $process->createTransition($runningSubJobs, $completed, JobAction::COMPLETE);
            $process->createTransition($runningSubJobs, $failed, JobAction::FAIL);
            $process->createTransition($runningSubJobs, $canceled, JobAction::CANCEL);
            $process->createTransition($runningSubJobs, $terminated, JobAction::TERMINATE);
        }

        if ($job->getSubJobPolicy()) {
            $process->createTransition($new, $terminated, JobAction::TERMINATE);
        }

        if ($exclusivePolicy = $job->getExclusivePolicy()) {
            if ($exclusivePolicy->isMarkJobAsCanceledOnDuplicateRun()) {
                $process->createTransition($new, $canceled, 'terminate_on_duplicate');
            } elseif ($exclusivePolicy->isMarkJobAsFailedOnDuplicateRun()) {
                $process->createTransition($new, $failed, 'terminate_on_duplicate');
            } else {
                throw new \LogicException(sprintf('The exclusive policy action "%s" is not supported', $exclusivePolicy->getOnDuplicateRun()));
            }
        }

        return $process;
    }

    public function can(string $action): ?Transition
    {
        if (false == $this->job instanceof Job) {
            throw new \LogicException('The method can be called for an instance of Job only.');
        }

        $sm = new StateMachine($this->process);

        return $sm->can($this->job->getCurrentResult()->getStatus(), $action);
    }

    public function getProcess(): PvmProcess
    {
        return $this->process;
    }

    private function createState(string $state): Node
    {
        $node = $this->process->createNode();
        $node->setLabel($state);

        return $node;
    }
}
