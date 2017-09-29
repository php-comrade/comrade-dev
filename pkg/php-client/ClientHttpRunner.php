<?php
namespace Comrade\Client;

use Comrade\Shared\Message\RunJob;
use Comrade\Shared\Model\JobResult;
use Comrade\Shared\Model\JobStatus;
use Comrade\Shared\Model\Throwable;
use Comrade\Shared\Message\JobResult as JobResultMessage;
use Enqueue\Util\JSON;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ClientHttpRunner
{
    /**
     * @var string
     */
    private $comradeUrl;

    /**
     * @param string $comradeUrl
     */
    public function __construct(string $comradeUrl)
    {
        $this->comradeUrl = $comradeUrl;
    }

    public function run(RequestInterface $message, callable $worker): ResponseInterface
    {
        $runJob = RunJob::create(JSON::decode($message->getBody()->getContents()));
        $metrics = null;

        try {
            $metrics = CollectMetrics::start();

            /** @var JobResultMessage $jobResultMessage */
            $jobResultMessage = call_user_func($worker, $runJob);

            if (false == $jobResultMessage instanceof JobResultMessage) {
                throw new \LogicException(sprintf('The worker must return instance of "%s"', JobResultMessage::class));
            }

            $metrics->stop()->updateResult($jobResultMessage->getResult());

            $jobResultMessage->setToken($runJob->getToken());
            $jobResultMessage->setJobId($runJob->getJob()->getId());

            return $this->sendResult($jobResultMessage);
        } catch (\Throwable $e) {
            $result = JobResult::createFor(JobStatus::STATUS_FAILED);
            $result->setError(Throwable::createFromThrowable($e));

            $metrics && $metrics->stop()->updateResult($result);

            $jobResultMessage = JobResultMessage::create();
            $jobResultMessage->setToken($runJob->getToken());
            $jobResultMessage->setJobId($runJob->getJob()->getId());
            $jobResultMessage->setResult($result);

            return $this->sendResult($jobResultMessage);
        }
    }

    private function sendResult(\Comrade\Shared\Message\JobResult $jobResultMessage): ResponseInterface
    {
        return new Response(200, ['Content-Type' => 'application/json'], JSON::encode($jobResultMessage));
    }
}