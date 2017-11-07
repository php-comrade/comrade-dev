<?php
namespace App\Pvm\Behavior;

use App\Commands;
use App\Model\JobResult;
use App\Model\PvmToken;
use App\Service\ChangeJobStateService;
use App\Service\PersistJobService;
use App\Topics;
use App\JobStatus;
use App\Storage\JobStorage;
use Comrade\Shared\Message\RunJob;
use Comrade\Shared\Model\HttpRunner;
use Comrade\Shared\Model\Job;
use Comrade\Shared\Model\JobAction;
use Comrade\Shared\Model\Throwable;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Exception\WaitExecutionException;
use Formapro\Pvm\SignalBehavior;
use Formapro\Pvm\Token;
use Formapro\Pvm\Transition;
use GuzzleHttp\Exception\GuzzleException;
use function Makasim\Values\get_values;

class HttpRunnerBehavior implements Behavior, SignalBehavior
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

    /**
     * @var PersistJobService
     */
    private $persistJobService;

    public function __construct(
        JobStorage $jobStorage,
        ProducerInterface $producer,
        ChangeJobStateService $changeJobStateService,
        PersistJobService $persistJobService
    ) {
        $this->jobStorage = $jobStorage;
        $this->producer = $producer;
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
        $job = $this->changeJobStateService->transitionInFlow($token->getJobId(), JobAction::RUN);

        /** @var HttpRunner $runner */
        $runner = $job->getRunner();

        $client = new \GuzzleHttp\Client();

        $httpRequest = new \GuzzleHttp\Psr7\Request(
            'POST',
            $runner->getUrl(),
            ['Content-Type' => 'application/json'],
            JSON::encode(RunJob::createFor($job, $token->getId()))
        );

        try {
            $httpResponse = $client->send($httpRequest, ['http_errors' => true]);
            if ($httpResponse->getStatusCode() === 204) {
                $this->changeJobStateService->transitionInFlow($job->getId(), JobAction::COMPLETE);
            } elseif ($httpResponse->getStatusCode() === 200) {
                $this->producer->sendCommand(Commands::HANDLE_RUNNER_RESULT, $httpResponse->getBody()->getContents());

                throw  new WaitExecutionException();
            } else {
                $this->changeJobStateService->transitionInFlow($job->getId(), JobAction::FAIL);
            }
        } catch (GuzzleException $e) {
            $this->changeJobStateService->changeInFlow($job->getId(), JobAction::FAIL, function(Job $job, Transition $transition) use ($e) {
                $result = JobResult::createFor($transition->getTo()->getLabel(), new \DateTime('now'));
                $result->setError(Throwable::createFromThrowable($e));

                $job->addResult($result);
                $job->setCurrentResult($result);

                return $job;
            });
        }
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
