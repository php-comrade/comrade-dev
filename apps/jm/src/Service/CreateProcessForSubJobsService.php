<?php
namespace App\Service;

use App\Infra\Uuid;
use App\Model\JobTemplate;
use App\Model\Process;
use App\Pvm\Behavior\IdleBehavior;
use App\Pvm\Behavior\RunJobBehavior;
use App\Pvm\Behavior\SimpleSynchronizeBehavior;

class CreateProcessForSubJobsService
{
    /**
     * @param JobTemplate[] $jobTemplates
     *
     * @return Process
     */
    public function createProcess(array $jobTemplates) : Process
    {
        $process = new Process();
        $process->setId(Uuid::generate());

        $startTask = $process->createNode();
        $startTask->setLabel('Start process');
        $startTask->setBehavior(IdleBehavior::class);
        $process->createTransition(null, $startTask);

        $failedTasks = [];
        $completedTasks = [];

        foreach ($jobTemplates as $jobTemplate) {
            $runJobTask = $process->createNode();
            $runJobTask->setLabel('Run job: '.$jobTemplate->getName());
            $runJobTask->setBehavior(RunJobBehavior::class);
            $process->addNodeJobTemplate($runJobTask, $jobTemplate);
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
