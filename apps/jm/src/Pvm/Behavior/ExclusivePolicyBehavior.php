<?php
namespace App\Pvm\Behavior;

use App\Model\Job;
use App\Model\JobAction;
use App\Model\PvmToken;
use App\Service\ChangeJobStateService;
use App\Topics;
use App\Model\JobResult;
use App\Storage\ExclusiveJobStorage;
use App\Storage\JobStorage;
use Enqueue\Client\ProducerInterface;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Token;
use Formapro\Pvm\Transition;
use function Makasim\Values\get_values;
use function Makasim\Values\set_value;
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
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var ChangeJobStateService
     */
    private $changeJobStateService;

    public function __construct(
        JobStorage $jobStorage,
        ExclusiveJobStorage $exclusiveJobStorage,
        ProducerInterface $producer,
        ChangeJobStateService $changeJobStateService
    ) {
        $this->jobStorage = $jobStorage;
        $this->exclusiveJobStorage = $exclusiveJobStorage;
        $this->producer = $producer;
        $this->changeJobStateService = $changeJobStateService;
    }

    /**
     * @param PvmToken $token
     *
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
        $job = $this->jobStorage->getOneById($token->getJobId());

        return $this->exclusiveJobStorage->lockByName($job->getName(), function() use ($token, $job) {
            $otherJobs = $this->jobStorage->count([
                '_id' => ['$ne' => get_object_id($job)],
                'name' => $job->getName(),
                'exclusivePolicy' => ['$exists' => true],
                'exclusive' => true,
                'finishedAt' => ['$exists' => false]
            ]);

            if (0 === $otherJobs) {
                set_value($job, 'exclusive', true);
                $this->jobStorage->update($job);

                $this->producer->sendEvent(Topics::JOB_UPDATED, get_values($job));

                return $token->getTransition()->getName();
            }

            /** @var Job $job */
            $job = $this->changeJobStateService->changeInFlow($job->getId(), 'terminate_on_duplicate', function(Job $job, Transition $transition) {
                $result = JobResult::createFor($transition->getTo()->getLabel());
                $job->addResult($result);
                $job->setCurrentResult($result);

                $this->jobStorage->update($job);

                return $job;
            });

            $this->producer->sendEvent(Topics::JOB_UPDATED, get_values($job));

            return 'finalize';
        });
    }
}
