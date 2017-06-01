<?php
namespace App\Service;

use App\Infra\Uuid;
use App\Model\GracePeriodPolicy;
use App\Model\Job;
use App\Model\Process;
use App\Pvm\Behavior\GracePeriodPolicyBehavior;
use App\Pvm\Behavior\IdleBehavior;
use App\Pvm\Behavior\RunJobBehavior;
use Formapro\Pvm\Node;
use function Makasim\Values\set_object;
use function Makasim\Values\set_value;

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
        $startTask->setLabel('Start process: '.$process->getId());
        $startTask->setBehavior(IdleBehavior::class);
        $process->createTransition(null, $startTask);

        $runJobTask = $process->createNode();
        $runJobTask->setLabel('Run job: '.$job->getUid());
        $runJobTask->setBehavior(RunJobBehavior::class);
        set_value($runJobTask, 'job.uid', $job->getUid());

        $process->createTransition($startTask, $runJobTask);

        $this->buildPolicyNodes($job, $startTask, $process);

        return $process;
    }
    
    private function buildPolicyNodes(Job $job, Node $startTask, Process $process)
    {
        foreach ($job->getPolices() as $policy) {
            if ($policy instanceof  GracePeriodPolicy) {
                $policyTask = $process->createNode();
                $policyTask->setLabel('Grace period for job: '.$job->getUid());
                $policyTask->setBehavior(GracePeriodPolicyBehavior::class);
                set_value($policyTask, 'job.uid', $job->getUid());
                set_object($policyTask, 'gracePeriodPolicy', $policy);

                $transition = $process->createTransition($startTask, $policyTask);
                $transition->setAsync(true);
            }
        }
    }
}
