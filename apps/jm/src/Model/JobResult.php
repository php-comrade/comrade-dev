<?php
namespace App\Model;

use App\JobStatus;

class JobResult extends \Comrade\Shared\Model\JobResult
{
    public function isNew():bool
    {
        return JobStatus::isNew($this);
    }

    public function isRunning():bool
    {
        return JobStatus::isRunning($this);
    }

    public function isCanceled():bool
    {
        return JobStatus::isCanceled($this);
    }

    public function isCompleted():bool
    {
        return JobStatus::isCompleted($this);
    }

    public function isDone():bool
    {
        return JobStatus::isDone($this);
    }

    public function isFailed():bool
    {
        return JobStatus::isFailed($this);
    }

    public function isRunSubJobs():bool
    {
        return JobStatus::isRunSubJobs($this);
    }

    public function isRunningSubJobs():bool
    {
        return JobStatus::isRunningSubJobs($this);
    }

    public function isTerminated():bool
    {
        return JobStatus::isTerminated($this);
    }
}
