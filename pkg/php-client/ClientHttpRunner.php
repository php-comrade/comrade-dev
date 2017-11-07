<?php
namespace Comrade\Client;

use Comrade\Shared\Message\RunnerResult;
use Comrade\Shared\Message\RunJob;
use Comrade\Shared\Model\JobAction;
use Comrade\Shared\Model\Throwable;
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

            $result = call_user_func($worker, $runJob);
            if (in_array($result, JobAction::getActions())) {
                $result = RunnerResult::createFor($runJob, $result);
                $result->setResultPayload('');
            } elseif(is_string($result)) {
                $result = RunnerResult::createFor($runJob, JobAction::COMPLETE);
                $result->setResultPayload($result);
            } elseif ($result instanceof RunnerResult) {
                // do nothing
            } else {
                $result = RunnerResult::createFor($runJob, JobAction::COMPLETE);
                $result->setResultPayload(JSON::encode($result));
            }

            $result->setMetrics($metrics->stop()->getMetrics());

            return $this->sendResult($result);
        } catch (\Throwable $e) {
            $result = RunnerResult::createFor($runJob, JobAction::FAIL);
            $result->setError(Throwable::createFromThrowable($e));

            $metrics && $result->setMetrics($metrics->stop()->getMetrics());

            return $this->sendResult($result);
        }
    }

    private function sendResult(RunnerResult $runnerResult): ResponseInterface
    {
        return new Response(200, ['Content-Type' => 'application/json'], JSON::encode($runnerResult));
    }
}