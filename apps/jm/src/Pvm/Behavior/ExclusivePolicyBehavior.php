<?php
namespace App\Pvm\Behavior;

use App\JobStatus;
use App\Model\Job;
use App\Model\JobResult;
use App\Model\Process;
use App\Storage\ExclusiveJobStorage;
use App\Storage\JobStorage;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Token;
use function Makasim\Yadm\get_object_id;

class ExclusivePolicyBehavior implements Behavior
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var ExclusiveJobStorage
     */
    private $exclusiveJobStorage;

    /**
     * @param JobStorage $jobStorage
     * @param ExclusiveJobStorage $exclusiveJobStorage
     */
    public function __construct(JobStorage $jobStorage, ExclusiveJobStorage $exclusiveJobStorage)
    {
        $this->jobStorage = $jobStorage;
        $this->exclusiveJobStorage = $exclusiveJobStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
        /** @var Process $process */
        $process = $token->getProcess();

        $job = $this->jobStorage->getOneById($process->getTokenJobId($token));

        return $this->exclusiveJobStorage->lockByName($job->getName(), function() use ($process, $token) {
            return $this->jobStorage->lockByJobId($process->getTokenJobId($token), function(Job $job) {
                $otherJobs =$this->jobStorage->count([
                    '_id' => ['$ne' => get_object_id($job)],
                    'name' => $job->getName(),
                    'exclusivePolicy' => ['$exists' => true],
                    'currentResult.status' => ['$bitsAnySet' => JobStatus::STATUS_RUNNING ]
                ]);

                if ($otherJobs == 0) {
                    $result = JobResult::createFor(JobStatus::STATUS_RUN_EXCLUSIVE);
                    $job->addResult($result);
                    $job->setCurrentResult($result);

                    $this->jobStorage->update($job);

                    return;
                }

                if ($job->getExclusivePolicy()->isMarkParentJobAsFailed()) {
                    $result = JobResult::createFor(JobStatus::STATUS_FAILED);
                    $job->addResult($result);
                    $job->setCurrentResult($result);

                    $this->jobStorage->update($job);

                    return ['failed'];
                }

                $result = JobResult::createFor(JobStatus::STATUS_CANCELED);
                $job->addResult($result);
                $job->setCurrentResult($result);

                $this->jobStorage->update($job);

                return ['canceled'];
            });
        });
    }
}
