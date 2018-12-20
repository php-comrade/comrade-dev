<?php
namespace App\Pvm\Behavior;

use App\JobStatus;
use App\Model\PvmToken;
use Comrade\Shared\Model\JobAction;
use App\Service\ChangeJobStateService;
use App\Model\JobResult;
use App\Storage\JobStorage;
use Comrade\Shared\Message\RunJob;
use Comrade\Shared\Model\Job;
use Comrade\Shared\Model\QueueRunner;
use Enqueue\Client\ProducerInterface;
use Formapro\Pvm\Transition;
use Interop\Queue\Context;
use Enqueue\Util\JSON;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Exception\WaitExecutionException;
use Formapro\Pvm\SignalBehavior;
use Formapro\Pvm\Token;

class QueueRunnerBehavior implements Behavior, SignalBehavior
{
    /**
     * @var Context
     */
    private $psrContext;

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
        Context $psrContext,
        JobStorage $jobStorage,
        ProducerInterface $producer,
        ChangeJobStateService $changeJobStateService
    ) {
        $this->psrContext = $psrContext;
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
        $job = $this->changeJobStateService->transitionInFlow($token->getJobId(), JobAction::RUN);

        /** @var QueueRunner $runner */
        $runner = $job->getRunner();

        $queue = $this->psrContext->createQueue($runner->getQueue());
        $message = $this->psrContext->createMessage(JSON::encode(RunJob::createFor($job, $token->getId())));
        $this->psrContext->createProducer()->send($queue, $message);

        throw new WaitExecutionException();
    }

    /**
     * @param PvmToken $token
     *
     * {@inheritdoc}
     */
    public function signal(Token $token)
    {
        $runnerResult = $token->getRunnerResult();

        /** @var Job $job */
        $job = $this->changeJobStateService->changeInFlow($token->getJobId(), $runnerResult->getAction(), function(Job $job, Transition $transition) use ($runnerResult) {
            $result = JobResult::createFor($transition->getTo()->getLabel(), \DateTime::createFromFormat('U', $runnerResult->getTimestamp()));

            if ($error = $runnerResult->getError()) {
                $result->setError($error);
            }

            if ($metrics = $runnerResult->getMetrics()) {
                $result->setMetrics($metrics);
            }

            $job->addResult($result);
            $job->setCurrentResult($result);
            $job->setResultPayload($runnerResult->getResultPayload());

            return $job;
        });

        if (JobStatus::RUNNING_SUB_JOBS === $job->getCurrentResult()->getStatus()) {
            return 'run_sub_jobs';
        }

        return 'finalize';
    }
}
