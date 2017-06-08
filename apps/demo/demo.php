<?php
namespace DemoApp;

use App\Async\WaitingForSubJobsResult;
use App\Async\DoJob;
use App\Async\Topics;
use App\Infra\Uuid;
use App\Infra\Yadm\ObjectBuilderHook;
use App\Model\Job;
use App\Async\JobResult as JobResultMessage;
use App\Model\JobResult;
use App\Model\JobTemplate;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LoggerExtension;
use Enqueue\Consumption\Extension\SignalExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Result;
use function Enqueue\dsn_to_context;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Util\JSON;
use function Makasim\Values\register_cast_hooks;
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
    WaitingForSubJobsResult::SCHEMA => WaitingForSubJobsResult::class,
    JobTemplate::SCHEMA => JobTemplate::class,
]))->register();

function createSubTasks(Job $job, PsrContext $context)
{
    $createSubJobs = WaitingForSubJobsResult::create();
    $createSubJobs->setJobId($job->getId());
    $createSubJobs->setToken($job->getProcessId());

    $jobTemplate = JobTemplate::create();
    $jobTemplate->setName('testSubJob1');
    $jobTemplate->setTemplateId(Uuid::generate());
    $jobTemplate->setProcessTemplateId(Uuid::generate());
    $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
    set_value($jobTemplate, 'enqueue.queue', 'demo_sub_job');
    $createSubJobs->addJobTemplate($jobTemplate);

    $jobTemplate = JobTemplate::create();
    $jobTemplate->setName('testSubJob2');
    $jobTemplate->setTemplateId(Uuid::generate());
    $jobTemplate->setProcessTemplateId(Uuid::generate());
    $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
    set_value($jobTemplate, 'enqueue.queue', 'demo_sub_job');
    $createSubJobs->addJobTemplate($jobTemplate);

    $jobTemplate = JobTemplate::create();
    $jobTemplate->setName('testSubJob3');
    $jobTemplate->setTemplateId(Uuid::generate());
    $jobTemplate->setProcessTemplateId(Uuid::generate());
    $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
    set_value($jobTemplate, 'enqueue.queue', 'demo_sub_job');
    $createSubJobs->addJobTemplate($jobTemplate);

    $jobTemplate = JobTemplate::create();
    $jobTemplate->setName('testSubJob4');
    $jobTemplate->setTemplateId(Uuid::generate());
    $jobTemplate->setProcessTemplateId(Uuid::generate());
    $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
    set_value($jobTemplate, 'enqueue.queue', 'demo_sub_job');
    $createSubJobs->addJobTemplate($jobTemplate);

    $createSubJobsQueue = $context->createQueue('enqueue.app.default');
    $message = $context->createMessage(JSON::encode($createSubJobs), [
        'enqueue.topic_name' => Topics::CREATE_SUB_JOBS,
    ]);

    $context->createProducer()->send($createSubJobsQueue, $message);
}

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

//    if (get_value($job, 'retryAttempts', 0) > 2) {
        $result = JobResult::createFor(Job::STATUS_COMPLETED);
//    } else {
//        $result = JobResult::createFor(Job::STATUS_FAILED);
//    }
    
//    createSubTasks($job, $context);

    $jobResultMessage = JobResultMessage::create();
    $jobResultMessage->setToken($data['token']);
    $jobResultMessage->setJobId($job->getId());
    $jobResultMessage->setResult($result);

    $feedbackQueue = $context->createQueue('enqueue.app.default');
    $message = $context->createMessage(JSON::encode($jobResultMessage), [
        'enqueue.topic_name' => Topics::JOB_RESULT,
        'enqueue.processor_queue_name' => 'enqueue.app.default',
        'enqueue.processor_name' => 'job_result',
    ]);

    $context->createProducer()->send($feedbackQueue, $message);

    return Result::ACK;
});

$queueConsumer->bind($subJobQueue, function(PsrMessage $message, PsrContext $context) {
    if ($message->isRedelivered()) {
        return Result::reject('The message was redelivered. Reject it');
    }

    $data = JSON::decode($message->getBody());

    $doJob = DoJob::create($data);

    $job = $doJob->getJob();

    $statuses = [Job::STATUS_FAILED, Job::STATUS_COMPLETED, Job::STATUS_COMPLETED, Job::STATUS_COMPLETED];

//    if (get_value($job, 'retryAttempts', 0) > 2) {
    $result = JobResult::createFor($statuses[rand(0, 3)]);
//    } else {
//        $result = JobResult::createFor(Job::STATUS_FAILED);
//    }

    $jobResultMessage = JobResultMessage::create();
    $jobResultMessage->setToken($data['token']);
    $jobResultMessage->setJobId($job->getId());
    $jobResultMessage->setResult($result);

    $feedbackQueue = $context->createQueue('enqueue.app.default');
    $message = $context->createMessage(JSON::encode($jobResultMessage), [
        'enqueue.topic_name' => Topics::JOB_RESULT,
        'enqueue.processor_queue_name' => 'enqueue.app.default',
        'enqueue.processor_name' => 'job_result',
    ]);

    $context->createProducer()->send($feedbackQueue, $message);

    return Result::ACK;
});

$queueConsumer->consume();