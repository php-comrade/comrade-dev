<?php
namespace App\Service;

use App\Model\PvmProcess;
use App\Storage\JobStorage;
use App\Storage\JobTemplateStorage;
use Comrade\Shared\Model\Job;
use Comrade\Shared\Model\JobTemplate;
use Formapro\Pvm\Node;
use function Makasim\Values\get_object;
use function Makasim\Values\set_object;

class CreateDependentJobsProcessService
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var JobTemplateStorage
     */
    private $jobTemplateStorage;

    public function __construct(JobStorage $jobStorage, JobTemplateStorage $jobTemplateStorage)
    {
        $this->jobStorage = $jobStorage;
        $this->jobTemplateStorage = $jobTemplateStorage;
    }

    public function createProcessForJob(Job $job) : PvmProcess
    {
        $process = $this->createProcessForJobTemplate($job);

        // TODO visualize progress

        return $process;
    }

    public function createProcessForJobTemplate(JobTemplate $jobTemplate) : PvmProcess
    {
        $process = PvmProcess::create();
        $process->setJobTemplateId($jobTemplate->getTemplateId());

        $node = $this->createNodeForJobTemplate($process, $jobTemplate);

        $process->createTransition(null, $node);

        $this->buildRelations($node);

        return $process;
    }

    private function buildRelations(Node $node): void
    {
        /** @var PvmProcess $process */
        $process = $node->getProcess();

        /** @var JobTemplate $jobTemplate */
        $jobTemplate = get_object($node, 'jobTemplate', JobTemplate::class);

        if ($jobTemplate->getRunSubJobsPolicy()) {
            foreach ($this->jobTemplateStorage->findSubJobTemplates($jobTemplate->getTemplateId()) as $subJobTemplate) {
                $subJobNode = $this->createNodeForJobTemplate($process, $subJobTemplate);

                $process->createTransition($node, $subJobNode, 'run_sub_jobs');
                $process->createTransition($subJobNode, $node, 'notify_parent_job');
            }
        }

        foreach ($jobTemplate->getRunDependentJobPolicies() as $policy) {
            $dependentJobTemplate = $this->jobTemplateStorage->findOne(['templateId' => $policy->getTemplateId()]);
            if (false == $dependentJobTemplate) {
                throw new \LogicException(sprintf('The job template with id "%s" could not be found', $policy->getTemplateId()));
            }

            $dependentJobNode = $this->createNodeForJobTemplate($process, $dependentJobTemplate);

            $process->createTransition(
                $node,
                $dependentJobNode,
                $policy->isRunAlways() ? 'always' : implode(',', $policy->getRunOnStatus())
            );

            $this->buildRelations($dependentJobNode);
        }
    }

    private function createNodeForJobTemplate(PvmProcess $process, JobTemplate $jobTemplate): Node
    {
        $node = $process->createNode();
        $node->setLabel($jobTemplate->getName());
        set_object($node, 'jobTemplate', $jobTemplate);

        return $node;
    }


//    private function createJobNode(PvmProcess $process, Job $job, Job $previousJob = null): Node
//    {
//        $transitionName = null;
//        if ($previousJob) {
//            foreach ($job->getRunDependentJobPolicies() as $policy) {
//                if ($policy->getTemplateId() === $job->getTemplateId()) {
//                    if ($policy->isRunAlways()) {
//                        $transitionName = 'always';
//                    } else {
//                        $transitionName = implode(',', $policy->getRunOnStatus());
//                    }
//                }
//            }
//        }
//
//        $jobNode = $process->createNode();
//        $transition = $process->createTransition($previousJob, $jobNode, $transitionName);
//        $token = $process->createToken($transition);
//
//        if (in_array($job->getCurrentResult()->getStatus(), [JobStatus::FAILED, JobStatus::CANCELED, JobStatus::COMPLETED, JobStatus::TERMINATED, JobStatus::RUNNING_SUB_JOBS])) {
//            $tokenTransition = TokenTransition::createFor($transition, 1);
//            $tokenTransition->setPassed();
//
//            $token->addTransition($tokenTransition);
//        } elseif (in_array($job->getCurrentResult()->getStatus(), [JobStatus::RETRYING, JobStatus::RUNNING])) {
//            $tokenTransition = TokenTransition::createFor($transition, 1);
//            $tokenTransition->setWaiting();
//
//            $token->addTransition($tokenTransition);
//        } else {
//            $tokenTransition = TokenTransition::createFor($transition, 1);
//            $tokenTransition->setOpened();
//
//            $token->addTransition($tokenTransition);
//        }
//
//        foreach ($this->jobStorage->getDependentJobs($job->getId()) as $dependentJob) {
//            $dependentJobNode = $this->createJobNode($process, $dependentJob, $job);
//        }
//
//
//    }
}
