<?php
namespace App\Service;

use App\Infra\Uuid;
use App\Model\Job;
use App\Model\Process;
use App\Pvm\Behavior\RunJobBehavior;
use function Makasim\Values\get_values;

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
        $task1->setValue('job', get_values($job));

        $process->createTransition(null, $task1);

        return $process;
    }
}