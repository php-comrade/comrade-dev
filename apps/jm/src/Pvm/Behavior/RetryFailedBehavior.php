<?php
namespace App\Pvm\Behavior;

use App\Infra\Pvm\NotAllowedTransitionException;
use App\Model\Job;
use App\Model\JobAction;
use App\Model\PvmToken;
use App\Service\ChangeJobStateService;
use App\Topics;
use App\Model\JobResult;
use App\Storage\JobStorage;
use App\Storage\ProcessExecutionStorage;
use Enqueue\Client\ProducerInterface;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Token;
use Formapro\Pvm\Transition;
use function Makasim\Values\get_values;

class RetryFailedBehavior implements Behavior
{
    /**
     * @var ProcessExecutionStorage
     */
    private $processExecutionStorage;

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
        ProcessExecutionStorage $processExecutionStorage,
        JobStorage $jobStorage,
        ProducerInterface $producer,
        ChangeJobStateService $changeJobStateService
    ) {
        $this->processExecutionStorage = $processExecutionStorage;
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
        try {
            /** @var Job $job */
            $job = $this->changeJobStateService->transitionInFlow($token->getJobId(), JobAction::RETRY, function(Job $job) {
                $job->getRetryFailedPolicy()->incrementRetryAttempts();
            });
        } catch (NotAllowedTransitionException $e) {
            return 'finalize';
        }

        $retryPolicy = $job->getRetryFailedPolicy();
        if ($retryPolicy->getRetryAttempts() <= $retryPolicy->getRetryLimit()) {
            return $job->getCurrentResult()->getStatus();
        }

        $this->changeJobStateService->transitionInFlow($job->getId(), JobAction::FAIL);

        return 'finalize';
    }
}
