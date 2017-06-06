<?php
namespace App\Service;

use App\Infra\Uuid;
use App\Model\GracePeriodPolicy;
use App\Model\Job;
use App\Model\Process;
use App\Model\RetryFailedPolicy;
use App\Pvm\Behavior\GracePeriodPolicyBehavior;
use App\Pvm\Behavior\IdleBehavior;
use App\Pvm\Behavior\RetryFailedBehavior;
use App\Pvm\Behavior\RunJobBehavior;
use function Makasim\Values\set_object;


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

        $startTask = $process->createNode();
        $startTask->setLabel('Start process');
        $startTask->setBehavior(IdleBehavior::class);
        $process->createTransition(null, $startTask);

        $runJobTask = $process->createNode();
        $runJobTask->setLabel('Run job: '.$job->getUid());
        $runJobTask->setBehavior(RunJobBehavior::class);
        $process->setNodeJob($runJobTask, $job);
        $process->createTransition($startTask, $runJobTask);

        $jobCompletedTask = $process->createNode();
        $jobCompletedTask->setLabel('Completed');
        $jobCompletedTask->setBehavior(IdleBehavior::class);
        $process->createTransition($runJobTask, $jobCompletedTask, 'completed');

        $onFailedTasks = [];

        $jobFailedTask = $process->createNode();
        $jobFailedTask->setLabel('Failed');
        $jobFailedTask->setBehavior(IdleBehavior::class);
        $runJobToFailedTransition = $process->createTransition($runJobTask, $jobFailedTask, 'failed');

        foreach ($job->getPolices() as $policy) {
            if ($policy instanceof  GracePeriodPolicy) {
                $now = new \DateTime('now');
                $diff = $now->diff($policy->getPeriodEndsAt());

                $policyTask = $process->createNode();
                $policyTask->setLabel('Grace period '.$diff->s.' seconds');
                $policyTask->setBehavior(GracePeriodPolicyBehavior::class);
                $process->setNodeJob($policyTask, $job);
                set_object($policyTask, 'gracePeriodPolicy', $policy);

                $transition = $process->createTransition($startTask, $policyTask);
                $transition->setAsync(true);

                $process->createTransition($policyTask, $jobFailedTask, 'failed');
                $process->createTransition($policyTask, $jobCompletedTask, 'completed');
            }

            if ($policy instanceof  RetryFailedPolicy) {
                $policyTask = $process->createNode();
                $policyTask->setLabel('Retries '.$policy->getRetryLimit());
                $policyTask->setBehavior(RetryFailedBehavior::class);
                $process->setNodeJob($policyTask, $job);
                set_object($policyTask, 'retryFailedPolicy', $policy);

                $onFailedTasks[] = $policyTask;

                $process->breakTransition($runJobToFailedTransition, $policyTask, 'failed');
                $process->createTransition($policyTask, $runJobTask, 'retry');
            }
        }

        return $process;
    }
}
