<?php
namespace App\Service;

use App\Infra\Uuid;
use App\Model\HttpRunner;
use App\Model\Job;
use App\Model\Process;
use App\Model\QueueRunner;
use App\Pvm\Behavior\HttpRunnerBehavior;
use App\Pvm\Behavior\IdleBehavior;
use App\Pvm\Behavior\NotifyParentProcessBehavior;
use App\Pvm\Behavior\QueueRunnerBehavior;
use App\Pvm\Behavior\SimpleSynchronizeBehavior;
use Formapro\Pvm\Token;

class CreateProcessForSubJobsService
{
    /**
     * @param Token $parentProcessToken
     * @param \Traversable|Job[] $jobs
     *
     * @return Process
     */
    public function createProcess(Token $parentProcessToken, \Traversable $jobs) : Process
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
            $runner = $job->getRunner();
            if ($runner instanceof QueueRunner) {
                $runnerTask = $process->createNode();
                $runnerTask->setLabel('Queue runner');
                $runnerTask->setBehavior(QueueRunnerBehavior::class);
                $process->addNodeJob($runnerTask, $job);
                $process->createTransition($startTask, $runnerTask)
                    ->setAsync(true)
                ;
            } elseif ($runner instanceof  HttpRunner) {
                $runnerTask = $process->createNode();
                $runnerTask->setLabel('Http runner');
                $runnerTask->setBehavior(HttpRunnerBehavior::class);
                $process->addNodeJob($runnerTask, $job);
                $process->createTransition($startTask, $runnerTask)
                    ->setAsync(true)
                ;
            } else {
                throw new \LogicException(sprintf('The runner "%s" is not supported.', get_class($runner)));
            }

            $jobCompletedTask = $process->createNode();
            $jobCompletedTask->setLabel('Completed');
            $jobCompletedTask->setBehavior(IdleBehavior::class);
            $process->createTransition($runnerTask, $jobCompletedTask, 'completed');
            $completedTasks[] = $jobCompletedTask;

            $jobFailedTask = $process->createNode();
            $jobFailedTask->setLabel('Failed');
            $jobFailedTask->setBehavior(IdleBehavior::class);
            $process->createTransition($runnerTask, $jobFailedTask, 'failed');
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

        $notifyParentProcessTask = $process->createNode();
        $notifyParentProcessTask->setLabel('Notify parent process');
        $notifyParentProcessTask->setValue('parentProcessId', $parentProcessToken->getProcess()->getId());
        $notifyParentProcessTask->setValue('parentProcessToken', $parentProcessToken->getId());
        $notifyParentProcessTask->setBehavior(NotifyParentProcessBehavior::class);

        $process->createTransition($synchronizeJobsTask, $notifyParentProcessTask);

        return $process;
    }
}
