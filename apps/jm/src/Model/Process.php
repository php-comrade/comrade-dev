<?php
namespace App\Model;

use Formapro\Pvm\Process as PvmProcess;

class Process extends PvmProcess
{
    /**
     * @param Job $job
     */
    public function addJob(Job $job)
    {
        $this->addObject('jobs', $job);
    }

    /**
     * @return Job|object|null
     */
    public function getJob($uid)
    {
        foreach ($this->getJobs() as $job) {
            if ($uid == $job->getUid()) {
                return $job;
            }
        }
    }

    /**
     * @return Job[]|\Traversable
     */
    public function getJobs()
    {
        return $this->getObjects('jobs');
    }
}
