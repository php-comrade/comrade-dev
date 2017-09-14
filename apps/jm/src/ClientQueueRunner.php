<?php
namespace App;

use App\Async\Commands;
use App\Async\RunJob;
use App\Model\JobResult;
use App\Model\Throwable;
use Enqueue\Util\JSON;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use App\Async\JobResult as JobResultMessage;

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

            /** @var JobResultMessage $jobResultMessage */
            $jobResultMessage = call_user_func($worker, $runJob);

            if (false == $jobResultMessage instanceof  JobResultMessage) {
                throw new \LogicException(sprintf('The worker must return instance of "%s"', JobResultMessage::class));
            }

            $metrics->stop()->updateResult($jobResultMessage->getResult());

            $jobResultMessage->setToken($runJob->getToken());
            $jobResultMessage->setJobId($runJob->getJob()->getId());

            $this->context->createProducer()->send(
                $this->context->createQueue(Commands::JOB_RESULT),
                $this->context->createMessage(JSON::encode($jobResultMessage))
            );
        } catch (\Throwable $e) {
            $result = JobResult::createFor(JobStatus::STATUS_FAILED);
            $result->setError(Throwable::createFromThrowable($e));

            $metrics && $metrics->stop()->updateResult($result);

            $jobResultMessage = JobResultMessage::create();
            $jobResultMessage->setToken($runJob->getToken());
            $jobResultMessage->setJobId($runJob->getJob()->getId());
            $jobResultMessage->setResult($result);

            $this->context->createProducer()->send(
                $this->context->createQueue(Commands::JOB_RESULT),
                $this->context->createMessage(JSON::encode($jobResultMessage))
            );
        }
    }
}