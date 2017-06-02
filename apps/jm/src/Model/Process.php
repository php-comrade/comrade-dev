<?php
namespace App\Model;

use Formapro\Pvm\Node;
use Formapro\Pvm\Process as PvmProcess;
use Formapro\Pvm\Token;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class Process extends PvmProcess
{
    /**
     * @param Job $job
     */
    public function addJob(Job $job):void
    {
        $this->addObject('jobs', $job);
    }

    /**
     * @return Job|object|null
     */
    public function getJob($uid):?Job
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
    public function getJobs():\Traversable
    {
        return $this->getObjects('jobs');
    }

    /**
     * @param Token $token
     *
     * @return Job
     */
    public function getTokenJob(Token $token):Job
    {
        return $this->getNodeJob($token->getTransition()->getTo());
    }

    /**
     * @param Node $node
     *
     * @return Job
     */
    public function getNodeJob(Node $node):Job
    {
        if (false == $jobId = get_value($node, 'jobId')) {
            throw new \LogicException('The jobId must be set');
        }

        /** @var Process $process */
        if ($this !== $node->getProcess()) {
            throw new \LogicException('The node process is not same as current.');
        }

        if (false == $job = $this->getJob($jobId)) {
            throw new \LogicException('The job could not be found');
        }

        return $job;
    }

    /**
     * @param Node $node
     * @param Job $job
     */
    public function setNodeJob(Node $node, Job $job):void
    {
        if (false == $this->getJob($job->getUid())) {
            $this->addJob($job);
        }

        set_value($node, 'jobId', $job->getUid());
    }
}
