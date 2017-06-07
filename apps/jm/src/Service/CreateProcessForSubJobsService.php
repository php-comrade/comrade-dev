<?php
namespace App\Service;

use App\Infra\Uuid;
use App\Model\Job;
use App\Model\Process;
use App\Pvm\Behavior\IdleBehavior;
use App\Pvm\Behavior\RunJobBehavior;
use App\Pvm\Behavior\SimpleSynchronizeBehavior;

class CreateProcessForSubJobsService
{
    /**
     * @param Job[] $jobs
     *
     * @return Process
     */
    public function createProcess(array $jobs) : Process
    {
        $process = new Process();
        $process->setId(Uuid::generate());

        $startTask = $process->createNode();
        $startTask->setLabel('Start process');
        $startTask->setBehavior(IdleBehavior::class);
        $process->createTransition(null, $startTask);

        $failedTasks = [];
        $completedTasks = [];

        foreach ($jobs as $job) {
            $runJobTask = $process->createNode();
            $runJobTask->setLabel('Run job: '.$job->getName());
            $runJobTask->setBehavior(RunJobBehavior::class);
            $process->setNodeJob($runJobTask, $job);
            $transition = $process->createTransition($startTask, $runJobTask);
            $transition->setAsync(true);

            $jobCompletedTask = $process->createNode();
            $jobCompletedTask->setLabel('Completed');
            $jobCompletedTask->setBehavior(IdleBehavior::class);
            $process->createTransition($runJobTask, $jobCompletedTask, 'completed');
            $completedTasks[] = $jobCompletedTask;

            $jobFailedTask = $process->createNode();
            $jobFailedTask->setLabel('Failed');
            $jobFailedTask->setBehavior(IdleBehavior::class);
            $process->createTransition($runJobTask, $jobFailedTask, 'failed');
            $failedTasks[] = $jobFailedTask;
        }

        $synchronizeJobsTask = $process->createNode();
        $synchronizeJobsTask->setLabel('Synchronize jobs');
        $synchronizeJobsTask->setValue('requiredWeight', count($completedTasks));
        $synchronizeJobsTask->setBehavior(SimpleSynchronizeBehavior::class);

        foreach ($completedTasks as $completedTask) {
            $process->createTransition($completedTask, $synchronizeJobsTask);
        }

        foreach ($failedTasks as $failedTask) {
            $process->createTransition($failedTask, $synchronizeJobsTask);
        }

        return $process;
    }
}
