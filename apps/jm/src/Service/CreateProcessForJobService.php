<?php
namespace App\Service;

use App\Model\JobTemplate;
use App\Model\Process;
use App\Model\QueueRunner;
use App\Pvm\Behavior\ExclusivePolicyBehavior;
use App\Pvm\Behavior\GracePeriodPolicyBehavior;
use App\Pvm\Behavior\IdleBehavior;
use App\Pvm\Behavior\RetryFailedBehavior;
use App\Pvm\Behavior\QueueRunnerBehavior;
use App\Pvm\Behavior\RunSubJobsProcessBehavior;

class CreateProcessForJobService
{
    /**
     * @param JobTemplate $jobTemplate
     *
     * @return Process
     */
    public function createProcess(JobTemplate $jobTemplate) : Process
    {
        $process = new Process();
        $process->setId($jobTemplate->getProcessTemplateId());

        $startTask = $process->createNode();
        $startTask->setLabel('Start process');
        $startTask->setBehavior(IdleBehavior::class);
        $process->createTransition(null, $startTask);

        $runner = $jobTemplate->getRunner();
        if ($runner instanceof QueueRunner) {
            $runnerTask = $process->createNode();
            $runnerTask->setLabel('Queue runner');
            $runnerTask->setBehavior(QueueRunnerBehavior::class);
            $process->addNodeJobTemplate($runnerTask, $jobTemplate);
            $startToRunTransition = $process->createTransition($startTask, $runnerTask);
        } else {
            throw new \LogicException(sprintf('The runner "%s" is not supported.', get_class($runner)));
        }

        $jobCompletedTask = $process->createNode();
        $jobCompletedTask->setLabel('Completed');
        $jobCompletedTask->setBehavior(IdleBehavior::class);
        $runJobToCompletedTransition = $process->createTransition($runnerTask, $jobCompletedTask, 'completed');

        $jobFailedTask = $process->createNode();
        $jobFailedTask->setLabel('Failed');
        $jobFailedTask->setBehavior(IdleBehavior::class);
        $runJobToFailedTransition = $process->createTransition($runnerTask, $jobFailedTask, 'failed');

        if ($policy = $jobTemplate->getExclusivePolicy()) {
            $policyTask = $process->createNode();
            $policyTask->setLabel('Exclusive job');
            $policyTask->setBehavior(ExclusivePolicyBehavior::class);
            $process->addNodeJobTemplate($policyTask, $jobTemplate);

            $startToRunTransition = $process->breakTransition($startToRunTransition, $policyTask);
            $process->createTransition($policyTask, $jobFailedTask, 'failed');
        }

        if ($policy = $jobTemplate->getGracePeriodPolicy()) {
            $policyTask = $process->createNode();
            $policyTask->setLabel('Grace period '.$jobTemplate->getGracePeriodPolicy()->getPeriod().' seconds');
            $policyTask->setBehavior(GracePeriodPolicyBehavior::class);
            $process->addNodeJobTemplate($policyTask, $jobTemplate);

            $transition = $process->createTransition($startTask, $policyTask);
            $transition->setAsync(true);

            $process->createTransition($policyTask, $jobFailedTask, 'failed');
        }

        if ($policy = $jobTemplate->getRetryFailedPolicy()) {
            $policyTask = $process->createNode();
            $policyTask->setLabel('Retries '.$policy->getRetryLimit());
            $policyTask->setBehavior(RetryFailedBehavior::class);
            $process->addNodeJobTemplate($policyTask, $jobTemplate);

            $runJobToFailedTransition = $process->breakTransition($runJobToFailedTransition, $policyTask, 'failed');
            $process->createTransition($policyTask, $runnerTask, 'retry');
        }

        if ($policy = $jobTemplate->getRunSubJobsPolicy()) {
            $policyTask = $process->createNode();
            $policyTask->setLabel('Run sub jobs');
            $policyTask->setBehavior(RunSubJobsProcessBehavior::class);
            $process->addNodeJobTemplate($policyTask, $jobTemplate);

            $process->createTransition($runnerTask, $policyTask, 'run_sub_jobs');
            $process->createTransition($policyTask, $jobFailedTask, 'failed');
            $process->createTransition($policyTask, $jobCompletedTask, 'completed');
        }

        return $process;
    }
}
