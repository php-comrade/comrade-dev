<?php
namespace App\Pvm\Behavior;

use App\JobStatus;
use App\Model\JobAction;
use App\Model\PvmToken;
use App\Service\ChangeJobStateService;
use App\Storage\JobStorage;
use App\Topics;
use Comrade\Shared\Model\SubJob;
use Enqueue\Client\ProducerInterface;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Token;
use function Makasim\Values\get_values;

class StartSubJobBehavior implements Behavior
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

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
        ProducerInterface $producer,
        ChangeJobStateService $changeJobStateService
    ) {
        $this->jobStorage = $jobStorage;
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
        /** @var SubJob $job */
        $job = $this->jobStorage->getOneById($token->getJobId());
        $parentJob = $this->jobStorage->getOneById($job->getParentId());

        if ($parentJob->getCurrentResult()->getStatus() !== JobStatus::RUNNING_SUB_JOBS) {
            $this->changeJobStateService->transitionInFlow($job->getId(), JobAction::TERMINATE);

            $this->producer->sendEvent(Topics::JOB_UPDATED, get_values($job));

            return 'finalize';
        }
    }
}
