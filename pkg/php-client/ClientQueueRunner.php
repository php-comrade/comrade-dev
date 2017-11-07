<?php
namespace Comrade\Client;

use Comrade\Shared\Message\RunnerResult;
use Comrade\Shared\Message\RunJob;
use Comrade\Shared\Model\JobAction;
use Comrade\Shared\Model\JobResult;
use Comrade\Shared\Model\Runner;
use Comrade\Shared\Model\Throwable;
use Enqueue\Util\JSON;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;

class ClientQueueRunner
{
    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @param PsrContext $context
     */
    public function __construct(PsrContext $context)
    {
        $this->context = $context;
    }

    public function run(PsrMessage $message, callable $worker): void
    {
        $runJob = RunJob::create(JSON::decode($message->getBody()));
        $metrics = null;

        try {
            $metrics = CollectMetrics::start();

            $result = call_user_func($worker, $runJob);
            if ($result instanceof RunnerResult) {
                // do nothing
            } else if (in_array($result, JobAction::getActions())) {
                $result = RunnerResult::createFor($runJob, $result);
            } else {
                $resultPayload = $result;
                $result = RunnerResult::createFor($runJob, JobAction::COMPLETE);
                $result->setResultPayload($resultPayload);
            }

            $result->setMetrics($metrics->stop()->getMetrics());

            $this->context->createProducer()->send(
                $this->context->createQueue('comrade_handle_runner_result'),
                $this->context->createMessage(JSON::encode($result))
            );
        } catch (\Throwable $e) {
            $result = RunnerResult::createFor($runJob, JobAction::FAIL);
            $result->setError(Throwable::createFromThrowable($e));

            $metrics && $result->setMetrics($metrics->stop()->getMetrics());

            $this->context->createProducer()->send(
                $this->context->createQueue('comrade_handle_runner_result'),
                $this->context->createMessage(JSON::encode($result))
            );

            throw $e;
        }
    }
}