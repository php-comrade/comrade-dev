<?php
namespace App\Pvm\Behavior;

use App\JobStatus;
use App\Model\JobAction;
use App\Model\PvmToken;
use App\Service\ChangeJobStateService;
use App\Storage\JobStorage;
use Comrade\Shared\Model\SubJob;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Token;

class StartSubJobBehavior implements Behavior
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var ChangeJobStateService
     */
    private $changeJobStateService;

    public function __construct(JobStorage $jobStorage, ChangeJobStateService $changeJobStateService)
    {
        $this->jobStorage = $jobStorage;
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

            return 'finalize';
        }
    }
}
