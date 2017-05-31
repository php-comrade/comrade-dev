<?php
namespace App\Service;

use App\Infra\Uuid;
use App\Model\Job;
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

        $task1 = $process->createNode();
        $task1->setBehavior(RunJobBehavior::class);
        $task1->setObject('job', $job);

        $process->createTransition(null, $task1, 'first');

        return $process;
    }
}