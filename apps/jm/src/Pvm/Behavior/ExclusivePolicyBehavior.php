<?php
namespace App\Pvm\Behavior;

use App\Model\PvmToken;
use App\Service\ChangeJobStateService;
use App\Service\PersistJobService;
use App\Storage\ExclusiveJobStorage;
use App\Storage\JobStorage;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Token;
use function Makasim\Values\get_value;
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
     * @var ChangeJobStateService
     */
    private $changeJobStateService;

    /**
     * @var PersistJobService
     */
    private $persistJobService;

    public function __construct(
        JobStorage $jobStorage,
        ExclusiveJobStorage $exclusiveJobStorage,
        ChangeJobStateService $changeJobStateService,
        PersistJobService $persistJobService
    ) {
        $this->jobStorage = $jobStorage;
        $this->exclusiveJobStorage = $exclusiveJobStorage;
        $this->changeJobStateService = $changeJobStateService;
        $this->persistJobService = $persistJobService;
    }

    /**
     * @param PvmToken $token
     *
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
        $job = $this->jobStorage->getOneById($token->getJobId());

        $this->exclusiveJobStorage->lockByName($job->getName(), function() use ($token, $job) {
            $otherJobs = $this->jobStorage->count([
                '_id' => ['$ne' => get_object_id($job)],
                'name' => $job->getName(),
                'exclusivePolicy' => ['$exists' => true],
                'exclusive' => true,
                'finishedAt' => ['$exists' => false]
            ]);

            if (0 === $otherJobs) {
                set_value($job, 'exclusive', true);

                $this->persistJobService->persist($job);
            }
        });

        if (get_value($job, 'exclusive')) {
            return $token->getTransition()->getName();
        }

        $this->changeJobStateService->transitionInFlow($job->getId(), 'terminate_on_duplicate');

        return 'finalize';
    }
}
