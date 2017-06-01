<?php
namespace App\Model;

use Formapro\Pvm\Node;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class JobNode extends Node
{
    /**
     * @param string $jobId
     */
    public function setJobId($jobId)
    {
        set_value($this,'job.uid', $jobId);
    }

    /**
     * @return string|null
     */
    public function getJobId()
    {
        return get_value($this,'job.uid');
    }

    /**
     * @return Job|null
     */
    public function getJob()
    {
        /** @var Process $process */
        if (false == $process = $this->getProcess()) {
            return;
        }

        if (false == $jobId = $this->getJobId()) {
            return;
        }

        return $process->getJob($jobId);
    }
}
