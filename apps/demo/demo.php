<?php
namespace DemoApp;

use App\Async\DoJob;
use App\Infra\Yadm\ObjectBuilderHook;
use App\Model\Job;
use App\Async\JobResult as JobResultMessage;
use App\Model\JobResult;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LoggerExtension;
use Enqueue\Consumption\Extension\SignalExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Result;
use function Enqueue\dsn_to_context;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Util\JSON;
use function Makasim\Values\build_object;
use function Makasim\Values\get_value;
use function Makasim\Values\register_cast_hooks;
use function Makasim\Values\register_object_hooks;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

require_once __DIR__.'/../jm/vendor/autoload.php';

class EchoLogger implements LoggerInterface
{
    use LoggerTrait;

    public function log($level, $message, array $context = array())
    {
        echo sprintf('[%s] %s %s ', $level, $message, json_encode($context)).PHP_EOL;
    }
}

register_cast_hooks();
register_object_hooks();

(new ObjectBuilderHook([
    Job::SCHEMA => Job::class,
    JobResult::SCHEMA => JobResult::class,
    DoJob::SCHEMA => DoJob::class,
    JobResultMessage::SCHEMA => JobResultMessage::class,
]))->register();

/** @var \Enqueue\AmqpExt\AmqpContext $c */
$c = dsn_to_context('amqp://guest:guest@rabbitmq:5672/jm?pre_fetch_count=1');

$queue = $c->createQueue('demo_job');
$queue->addFlag(AMQP_DURABLE);
$c->declareQueue($queue);

$subJobQueue = $c->createQueue('demo_sub_job');
$subJobQueue->addFlag(AMQP_DURABLE);
$c->declareQueue($subJobQueue);

$queueConsumer = new QueueConsumer($c, new ChainExtension([
    new LoggerExtension(new EchoLogger()),
    new SignalExtension(),
]));

$queueConsumer->bind($queue, function(PsrMessage $message, PsrContext $context) {
    if ($message->isRedelivered()) {
        return Result::reject('The message was redelivered. Reject it');
    }

    $data = JSON::decode($message->getBody());

    $doJob = DoJob::create($data);

    $job = $doJob->getJob();

    if (get_value($job, 'retryAttempts', 0) > 2) {
        $result = JobResult::createFor(Job::STATUS_COMPLETED);
    } else {
        $result = JobResult::createFor(Job::STATUS_FAILED);
    }

    $jobResultMessage = JobResultMessage::create();
    $jobResultMessage->setToken($data['token']);
    $jobResultMessage->setJobId($job->getId());
    $jobResultMessage->setResult($result);

    $feedbackQueue = $context->createQueue('enqueue.app.default');
    $message = $context->createMessage(JSON::encode($jobResultMessage), [
        'enqueue.topic_name' => 'job_manager.process_feedback',
        'enqueue.processor_queue_name' => 'enqueue.app.default',
        'enqueue.processor_name' => 'job_manager_process_feedback',
    ]);

    $context->createProducer()->send($feedbackQueue, $message);

    return Result::ACK;
});

//$queueConsumer->bind($subJobQueue, function(PsrMessage $message, PsrContext $context) {
//    if ($message->isRedelivered()) {
//        return Result::reject('The message was redelivered. Reject it');
//    }
//
//    $data = JSON::decode($message->getBody());
//
//    $status = [Job::STATUS_FAILED, Job::STATUS_COMPLETED];
//
//    /** @var Job $job */
//    $job = build_object(Job::class, $data['job']);
//    /** @var JobResult $jobFeedback */
//
//    $job->setStatus($status[rand(0, 1)]);
//    $jobFeedback = build_object(JobResult::class, [
//        'finished' => true,
//        'schema' => JobResult::SCHEMA,
//    ]);
//
//    /** @var JobResult $feedbackMessage */
//    $feedbackMessage = build_object(JobResult::class, []);
//    $feedbackMessage->setToken($data['token']);
//    $feedbackMessage->setJob($job);
//    $feedbackMessage->setJobFeedback($jobFeedback);
//
//    $feedbackQueue = $context->createQueue('enqueue.app.default');
//    $message = $context->createMessage(JSON::encode($feedbackMessage), [
//        'enqueue.topic_name' => 'job_manager.process_feedback',
//        'enqueue.processor_queue_name' => 'enqueue.app.default',
//        'enqueue.processor_name' => 'job_manager_process_feedback',
//    ]);
//
//    $context->createProducer()->send($feedbackQueue, $message);
//
//    return Result::ACK;
//});

$queueConsumer->consume();