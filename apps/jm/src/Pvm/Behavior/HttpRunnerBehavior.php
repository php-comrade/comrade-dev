<?php
namespace App\Pvm\Behavior;

use App\Commands;
use App\Model\JobResult;
use App\Topics;
use App\JobStatus;
use App\Model\Process;
use App\Storage\JobStorage;
use Comrade\Shared\Message\RunJob;
use Comrade\Shared\Model\HttpRunner;
use Comrade\Shared\Model\Throwable;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Token;
use GuzzleHttp\Exception\GuzzleException;
use function Makasim\Values\get_values;

class HttpRunnerBehavior implements Behavior
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
     * @param JobStorage        $jobStorage
     * @param ProducerInterface $producer
     */
    public function __construct(JobStorage $jobStorage, ProducerInterface $producer)
    {
        $this->jobStorage = $jobStorage;
        $this->producer = $producer;
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

        /** @var HttpRunner $runner */
        $runner = $job->getRunner();

        $client = new \GuzzleHttp\Client();

        $httpRequest = new \GuzzleHttp\Psr7\Request(
            'POST',
            $runner->getUrl(),
            ['Content-Type' => 'application/json'],
            JSON::encode(RunJob::createFor($job, $token->getId()))
        );

        $result = JobResult::create();
        $result->setStatus(JobStatus::STATUS_RUNNING);
        $result->setCreatedAt(new \DateTime('now'));
        $job->addResult($result);
        $job->setCurrentResult($result);
        $this->jobStorage->update($job);
        $this->producer->sendEvent(Topics::UPDATE_JOB, get_values($job));

        try {
            $httpResponse = $client->send($httpRequest, ['http_errors' => true]);
            if ($httpResponse->getStatusCode() === 204) {
                $job->addResult(JobResult::createFor(JobStatus::STATUS_COMPLETED, new \DateTime('now')));
                $this->jobStorage->update($job);

                return 'completed';
            } elseif ($httpResponse->getStatusCode() === 200) {
                $this->producer->sendCommand(Commands::JOB_RESULT, $httpResponse->getBody()->getContents());
            } else {
                $job->addResult(JobResult::createFor(JobStatus::STATUS_FAILED, new \DateTime('now')));
                $this->jobStorage->update($job);

                return ['failed'];
            }
        } catch (GuzzleException $e) {
            $result = JobResult::createFor(JobStatus::STATUS_FAILED, new \DateTime('now'));
            $result->setError(Throwable::createFromThrowable($e));
            $job->addResult($result);
            $this->jobStorage->update($job);

            return ['failed'];
        }
    }
}
