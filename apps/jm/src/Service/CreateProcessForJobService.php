<?php
namespace App\Service;

use App\Infra\Uuid;
use App\Model\Job;
use App\Model\JobNode;
use App\Model\Process;
use App\Pvm\Behavior\RunJobBehavior;

class CreateProcessForJobService
{
    /**
     * @param Job $job
     *
     * @return Process
     */
    public function createProcess(Job $job) : Process
    {
        $process = new Process();
        $process->setId(Uuid::generate());
        $process->addJob($job);

        $task1 = new JobNode();
        $task1->setJobId($job->getUid());
        $task1->setLabel('');
        $task1->setBehavior(RunJobBehavior::class);
        $process->registerNode($task1);

        $process->createTransition(null, $task1);

        return $process;
    }
}
