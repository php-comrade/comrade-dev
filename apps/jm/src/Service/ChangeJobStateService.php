<?php
namespace App\Service;

use App\Infra\Pvm\NotAllowedTransitionException;
use App\JobStateMachine;
use App\Model\Job;
use App\Model\JobResult;
use App\Storage\JobStorage;
use Formapro\Pvm\Exception\InterruptExecutionException;
use Formapro\Pvm\Transition;

class ChangeJobStateService
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var PersistJobService
     */
    private $persistJobService;

    public function __construct(JobStorage $jobStorage, PersistJobService $persistJobService)
    {
        $this->jobStorage = $jobStorage;
        $this->persistJobService = $persistJobService;
    }

    public function can(Job $job, string $action): ?Transition
    {
        $jsm = new JobStateMachine($job);

        return $jsm->can($action);
    }

    public function change(string $jobId, string $action, callable $onChange)
    {
        return $this->jobStorage->lockByJobId($jobId, function(Job $job, JobStorage $jobStorage) use ($action, $onChange) {
            $jsm = new JobStateMachine($job);
            if (false == $transition = $jsm->can($action)) {
                throw NotAllowedTransitionException::fromNodeWithAction($job->getCurrentResult()->getStatus(), $action);
            }

            $result = call_user_func($onChange, $job, $transition);

            $this->persistJobService->persist($job);

            return $result;
        });
    }

    public function changeInFlow(string $jobId, string $action, callable $onChange)
    {
        try {
            return $this->change($jobId, $action, $onChange);
        } catch (NotAllowedTransitionException $e) {
            throw new InterruptExecutionException($e->getMessage(), null, $e);
        }
    }

    public function transition(string $jobId, string $action, callable $onChange = null): Job
    {
        return $this->change($jobId, $action, function(Job $job, Transition $transition) use ($onChange) {
            $result = JobResult::createFor($transition->getTo()->getLabel());

            $job->addResult($result);
            $job->setCurrentResult($result);

            if ($onChange) {
                call_user_func($onChange, $job, $transition);
            }

            return $job;
        });
    }

    public function transitionInFlow(string $jobId, string $action, callable $onChange = null): Job
    {
        try {
            return $this->transition($jobId, $action, $onChange);
        } catch (NotAllowedTransitionException $e) {
            throw new InterruptExecutionException($e->getMessage(), null, $e);
        }
    }
}
