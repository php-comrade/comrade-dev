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
            $job = $this->changeJobStateService->change($token->getJobId(), JobAction::RETRY, function(Job $job, Transition $transition) {
                $result = JobResult::createFor($transition->getTo()->getLabel());
                $job->addResult($result);
                $job->setCurrentResult($result);

                $job->getRetryFailedPolicy()->incrementRetryAttempts();

                return $job;
            });
        } catch (NotAllowedTransitionException $e) {
            return 'finalize';
        }

        $this->producer->sendEvent(Topics::JOB_UPDATED, get_values($job));
        $retryPolicy = $job->getRetryFailedPolicy();
        if ($retryPolicy->getRetryAttempts() <= $retryPolicy->getRetryLimit()) {
            return $job->getCurrentResult()->getStatus();
        }

        /** @var Job $job */
        $job = $this->changeJobStateService->changeInFlow($job->getId(), JobAction::FAIL, function(Job $job, Transition $transition) {
            $result = JobResult::createFor($transition->getTo()->getLabel());
            $job->addResult($result);
            $job->setCurrentResult($result);

            return $job;
        });

        $this->producer->sendEvent(Topics::JOB_UPDATED, get_values($job));

        return 'finalize';
    }
}
