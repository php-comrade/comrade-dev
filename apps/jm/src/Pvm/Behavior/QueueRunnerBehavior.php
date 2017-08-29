<?php
namespace App\Pvm\Behavior;

use App\Async\RunJob;
use App\JobStatus;
use App\Model\Job;
use App\Model\JobResult;
use App\Model\Process;
use App\Model\QueueRunner;
use App\Storage\JobStorage;
use Interop\Queue\PsrContext;
use Enqueue\Util\JSON;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Exception\WaitExecutionException;
use Formapro\Pvm\SignalBehavior;
use Formapro\Pvm\Token;
use function Makasim\Values\get_value;

class QueueRunnerBehavior implements Behavior, SignalBehavior
{
    /**
     * @var PsrContext
     */
    private $psrContext;

    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @param PsrContext $psrContext
     * @param JobStorage $jobStorage
     */
    public function __construct(
        PsrContext $psrContext,
        JobStorage $jobStorage
    ) {
        $this->psrContext = $psrContext;
        $this->jobStorage = $jobStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
        /** @var Process $process */
        $process = $token->getProcess();
        $job = $this->jobStorage->getOneById($process->getTokenJobId($token));
        if ($job->getCurrentResult()->isCompleted()) {
            return ['completed'];
        }
        if ($job->getCurrentResult()->isFailed()) {
            return ['failed'];
        }

        /** @var QueueRunner $runner */
        $runner = $job->getRunner();

        if ($runner->getConnectionDsn()) {
            throw new \LogicException('Not implemented yet');
        }

        $queue = $this->psrContext->createQueue($runner->getQueue());
        $message = $this->psrContext->createMessage(JSON::encode(RunJob::createFor($job, $token)));

        $result = JobResult::create();
        $result->setStatus(JobStatus::STATUS_RUNNING);
        $result->setCreatedAt(new \DateTime('now'));
        $job->addResult($result);
        $job->setCurrentResult($result);

        $this->jobStorage->update($job);
        $this->psrContext->createProducer()->send($queue, $message);

        throw new WaitExecutionException();
    }

    /**
     * {@inheritdoc}
     */
    public function signal(Token $token)
    {
        /** @var Process $process */
        $process = $token->getProcess();
        $job = $this->jobStorage->getOneById($process->getTokenJobId($token));
        $result = $job->getCurrentResult();
        if ($result->isFailed()) {
            return ['failed'];
        }

        if ($result->isRunSubJobs()) {
            return ['run_sub_jobs'];
        }

        if ($result->isCompleted() || $result->isCanceled() || $result->isTerminated()){
            return ['completed'];
        }

        if ($result->isRunning() || $result->isNew()) {
            throw new WaitExecutionException();
        }

        throw new \LogicException(sprintf('Status "%s"is not supported', $result->getStatus()));
    }
}
