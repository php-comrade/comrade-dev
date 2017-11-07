<?php
namespace App\Service;

use App\Model\PvmProcess;
use App\Pvm\Behavior\ExclusivePolicyBehavior;
use App\Pvm\Behavior\FinalizeJobBehavior;
use App\Pvm\Behavior\GracePeriodPolicyBehavior;
use App\Pvm\Behavior\HttpRunnerBehavior;
use App\Pvm\Behavior\IdleBehavior;
use App\Pvm\Behavior\NotifyParentProcessBehavior;
use App\Pvm\Behavior\RetryFailedBehavior;
use App\Pvm\Behavior\QueueRunnerBehavior;
use App\Pvm\Behavior\RunDependentJobsBehavior;
use App\Pvm\Behavior\RunSubJobsProcessBehavior;
use App\Pvm\Behavior\StartJobBehavior;
use App\Pvm\Behavior\StartSubJobBehavior;
use App\Pvm\Behavior\WaitSubJobsProcessBehavior;
use Comrade\Shared\Model\HttpRunner;
use App\Model\JobTemplate;
use Comrade\Shared\Model\JobStatus;
use Comrade\Shared\Model\QueueRunner;

class CreateProcessForJobService
{
    /**
     * @param JobTemplate $jobTemplate
     *
     * @return PvmProcess
     */
    public function createProcess(JobTemplate $jobTemplate) : PvmProcess
    {
        $process = PvmProcess::create();
        $process->setId($jobTemplate->getProcessTemplateId());
        $process->setJobTemplateId($jobTemplate->getTemplateId());

        $startTask = $process->createNode();
        $startTask->setLabel('Start process');
        $startTask->setBehavior(StartJobBehavior::class);
        $process->createTransition(null, $startTask);

        $runner = $jobTemplate->getRunner();
        if ($runner instanceof QueueRunner) {
            $runnerTask = $process->createNode();
            $runnerTask->setLabel('Queue runner');
            $runnerTask->setBehavior(QueueRunnerBehavior::class);
            $startToRunTransition = $process->createTransition($startTask, $runnerTask);
        } elseif ($runner instanceof  HttpRunner) {
            $runnerTask = $process->createNode();
            $runnerTask->setLabel('Http runner');
            $runnerTask->setBehavior(HttpRunnerBehavior::class);
            $startToRunTransition = $process->createTransition($startTask, $runnerTask);
        } else {
            throw new \LogicException(sprintf('The runner "%s" is not supported.', get_class($runner)));
        }

        $finalizeJobTask = $process->createNode();
        $finalizeJobTask->setLabel('Finalize');
        $finalizeJobTask->setBehavior(FinalizeJobBehavior::class);
        $runToFinalizeTransition = $process->createTransition($runnerTask, $finalizeJobTask, 'finalize');

        if ($subJobPolicy = $jobTemplate->getSubJobPolicy()) {
            $startSubJobTask = $process->createNode();
            $startSubJobTask->setLabel('Start sub job');
            $startSubJobTask->setBehavior(StartSubJobBehavior::class);

            $startToRunTransition = $process->breakTransition($startToRunTransition, $startSubJobTask);
            $process->createTransition($startSubJobTask, $finalizeJobTask, 'finalize');

            $startTask = $startSubJobTask;
        }

        if ($exclusivePolicy = $jobTemplate->getExclusivePolicy()) {
            $policyTask = $process->createNode();
            $policyTask->setLabel('Exclusive job');
            $policyTask->setBehavior(ExclusivePolicyBehavior::class);

            $startToRunTransition = $process->breakTransition($startToRunTransition, $policyTask, $startToRunTransition->getName());
            $process->createTransition($policyTask, $finalizeJobTask, 'finalize');
        }

        if ($gracePeriod = $jobTemplate->getGracePeriodPolicy()) {
            $gracePeriodTask = $process->createNode();
            $gracePeriodTask->setLabel('Grace period '.$jobTemplate->getGracePeriodPolicy()->getPeriod().' seconds');
            $gracePeriodTask->setBehavior(GracePeriodPolicyBehavior::class);

            $transition = $process->createTransition($startTask, $gracePeriodTask);
            $transition->setAsync(true);

            $process->createTransition($gracePeriodTask, $finalizeJobTask);
        }

        if ($retryPolicy = $jobTemplate->getRetryFailedPolicy()) {
            $retryTask = $process->createNode();
            $retryTask->setLabel('Retries '.$retryPolicy->getRetryLimit());
            $retryTask->setBehavior(RetryFailedBehavior::class);

            $runToFinalizeTransition = $process->breakTransition($runToFinalizeTransition, $retryTask, 'finalize');
            $retryToRunTransition = $process->createTransition($retryTask, $runnerTask, JobStatus::RETRYING);
        }

        if ($subJobPolicy = $jobTemplate->getSubJobPolicy()) {
            $notifyParentTask = $process->createNode();
            $notifyParentTask->setLabel('Notify parent');
            $notifyParentTask->setBehavior(NotifyParentProcessBehavior::class);

            $runToFinalizeTransition = $process->breakTransition($runToFinalizeTransition, $notifyParentTask, 'finalize');
        }

        if ($runSubJobsPolicy = $jobTemplate->getRunSubJobsPolicy()) {
            $runSubJobsTask = $process->createNode();
            $runSubJobsTask->setLabel('Run sub jobs');
            $runSubJobsTask->setBehavior(RunSubJobsProcessBehavior::class);

            $waitSubJobsTask = $process->createNode();
            $waitSubJobsTask->setLabel('Wait sub jobs');
            $waitSubJobsTask->setBehavior(WaitSubJobsProcessBehavior::class);

            $subJobNotificationTask = $process->createNode();
            $subJobNotificationTask->setLabel('Notification from sub job');
            $subJobNotificationTask->setBehavior(IdleBehavior::class);

            $runnerToRunSubJobsTransition = $process->createTransition($runnerTask, $runSubJobsTask, 'run_sub_jobs');
            $runnerToRunSubJobsTransition->setAsync(true);
            $process->createTransition($runSubJobsTask, $waitSubJobsTask, 'wait_sub_jobs');
            $process->createTransition($subJobNotificationTask, $waitSubJobsTask, 'sub_job_notification');
            $process->createTransition($waitSubJobsTask, $finalizeJobTask, 'finalize');
        }

        if ($jobTemplate->getRunDependentJobPolicies()) {
            $runDependentJobsTask = $process->createNode();
            $runDependentJobsTask->setLabel('Run dependent jobs');
            $runDependentJobsTask->setBehavior(RunDependentJobsBehavior::class);

            $transition = $process->createTransition($finalizeJobTask, $runDependentJobsTask);
            $transition->setAsync(true);
        }

        return $process;
    }
}
