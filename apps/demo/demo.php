<?php
namespace DemoApp;

use App\Async\ProcessFeedback;
use App\Model\Job;
use App\Model\JobFeedback;
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
use function Makasim\Values\register_object_hooks;
use function Makasim\Values\set_value;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

require_once __DIR__.'/../jm/vendor/autoload.php';

class EchoLogger implements LoggerInterface
{
    use LoggerTrait;

    public function log($level, $message, array $context = array())
    {
        echo sprintf('[%s] %s %s', $level, $message, json_encode($context)).PHP_EOL;
    }
}

register_object_hooks();

/** @var \Enqueue\AmqpExt\AmqpContext $c */
$c = dsn_to_context('amqp://guest:guest@rabbitmq:5672/jm?pre_fetch_count=1');

$queue = $c->createQueue('demo_job');
$queue->addFlag(AMQP_DURABLE);
$c->declareQueue($queue);

$queueConsumer = new QueueConsumer($c, new ChainExtension([
    new LoggerExtension(new EchoLogger()),
    new SignalExtension(),
]));

$queueConsumer->bind($queue, function(PsrMessage $message, PsrContext $context) {
    $data = JSON::decode($message->getBody());

    /** @var Job $job */
    $job = build_object(Job::class, $data['job']);
    /** @var JobFeedback $jobFeedback */

    if (get_value($job, 'retryAttempts', 0) > 2) {
        $job->setStatus(Job::STATUS_COMPLETED);
        $jobFeedback = build_object(JobFeedback::class, [
            'finished' => true,
            'schema' => JobFeedback::SCHEMA,
        ]);
    } else {
        $job->setStatus(Job::STATUS_FAILED);
        $jobFeedback = build_object(JobFeedback::class, [
            'finished' => false,
            'failed' => true,
            'schema' => JobFeedback::SCHEMA,
        ]);
    }

    /** @var ProcessFeedback $feedbackMessage */
    $feedbackMessage = build_object(ProcessFeedback::class, []);
    $feedbackMessage->setToken($data['token']);
    $feedbackMessage->setJob($job);
    $feedbackMessage->setJobFeedback($jobFeedback);

    $feedbackQueue = $context->createQueue('enqueue.app.default');
    $message = $context->createMessage(JSON::encode($feedbackMessage), [
        'enqueue.topic_name' => 'job_manager.process_feedback',
        'enqueue.processor_queue_name' => 'enqueue.app.default',
        'enqueue.processor_name' => 'job_manager_process_feedback',
    ]);

    $context->createProducer()->send($feedbackQueue, $message);

    return Result::ACK;
});

$queueConsumer->consume();