<?php
namespace App\Service;

use App\Model\PvmProcess;
use App\Pvm\Behavior\ExclusivePolicyBehavior;
use App\Pvm\Behavior\FinalizeJobBehavior;
use App\Pvm\Behavior\GracePeriodPolicyBehavior;
use App\Pvm\Behavior\HttpRunnerBehavior;
use App\Pvm\Behavior\IdleBehavior;
use App\Pvm\Behavior\RetryFailedBehavior;
use App\Pvm\Behavior\QueueRunnerBehavior;
use App\Pvm\Behavior\RunSubJobsProcessBehavior;
use App\Pvm\Behavior\StartJobBehavior;
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
            $retryTask->setLabel('Retries '.$retryPolicy->getRetryAttempts().'/'.$retryPolicy->getRetryLimit());
            $retryTask->setBehavior(RetryFailedBehavior::class);

            $runToFinalizeTransition = $process->breakTransition($runToFinalizeTransition, $retryTask, 'finalize');
            $retryToRunTransition = $process->createTransition($retryTask, $runnerTask, JobStatus::RETRYING);
        }

//        if ($policy = $jobTemplate->getRunSubJobsPolicy()) {
//            $policyTask = $process->createNode();
//            $policyTask->setLabel('Run sub jobs');
//            $policyTask->setBehavior(RunSubJobsProcessBehavior::class);
//
//            $process->createTransition($runnerTask, $policyTask, JobStatus::RUNNING_SUB_JOBS);
//            $process->createTransition($policyTask, $failedTask, JobStatus::FAILED);
//            $process->createTransition($policyTask, $completedTask, JobStatus::COMPLETED);
//        }

        return $process;
    }
}
