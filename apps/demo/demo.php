<?php
namespace DemoApp;

use App\Async\RunSubJobsResult;
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
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__.'/../jm/vendor/autoload.php';


$output = new ConsoleOutput(ConsoleOutput::VERBOSITY_QUIET);
$logger = new ConsoleLogger($output);

register_cast_hooks();
register_object_hooks();

(new ObjectBuilderHook([
    Job::SCHEMA => Job::class,
    JobResult::SCHEMA => JobResult::class,
    DoJob::SCHEMA => DoJob::class,
    JobResultMessage::SCHEMA => JobResultMessage::class,
    RunSubJobsResult::SCHEMA => RunSubJobsResult::class,
    JobTemplate::SCHEMA => JobTemplate::class,
    RunSubJobsResult::SCHEMA => RunSubJobsResult::class,
]))->register();

function createSubTasks(Job $job, PsrContext $context, array $data):RunSubJobsResult
{
    $jobResultMessage = RunSubJobsResult::create();
    $jobResultMessage->setToken($data['token']);
    $jobResultMessage->setJobId($job->getId());
    $jobResultMessage->setResult(JobResult::createFor(Job::STATUS_RUN_SUB_JOBS));

    $jobTemplate = JobTemplate::create();
    $jobTemplate->setName('testSubJob1');
    $jobTemplate->setTemplateId(Uuid::generate());
    $jobTemplate->setProcessTemplateId(Uuid::generate());
    $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
    set_value($jobTemplate, 'enqueue.queue', 'demo_sub_job');
    $jobResultMessage->addJobTemplate($jobTemplate);

    $jobTemplate = JobTemplate::create();
    $jobTemplate->setName('testSubJob2');
    $jobTemplate->setTemplateId(Uuid::generate());
    $jobTemplate->setProcessTemplateId(Uuid::generate());
    $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
    set_value($jobTemplate, 'enqueue.queue', 'demo_sub_job');
    $jobResultMessage->addJobTemplate($jobTemplate);

    $jobTemplate = JobTemplate::create();
    $jobTemplate->setName('testSubJob3');
    $jobTemplate->setTemplateId(Uuid::generate());
    $jobTemplate->setProcessTemplateId(Uuid::generate());
    $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
    set_value($jobTemplate, 'enqueue.queue', 'demo_sub_job');
    $jobResultMessage->addJobTemplate($jobTemplate);

    $jobTemplate = JobTemplate::create();
    $jobTemplate->setName('testSubJob4');
    $jobTemplate->setTemplateId(Uuid::generate());
    $jobTemplate->setProcessTemplateId(Uuid::generate());
    $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
    set_value($jobTemplate, 'enqueue.queue', 'demo_sub_job');
    $jobResultMessage->addJobTemplate($jobTemplate);

    return $jobResultMessage;
}

/** @var \Enqueue\AmqpExt\AmqpContext $c */
$c = dsn_to_context('amqp://guest:guest@rabbitmq:5672/jm?pre_fetch_count=4');

$queue = $c->createQueue('demo_job');
$queue->addFlag(AMQP_DURABLE);
$c->declareQueue($queue);

$subJobQueue = $c->createQueue('demo_sub_job');
$subJobQueue->addFlag(AMQP_DURABLE);
$c->declareQueue($subJobQueue);

$queueConsumer = new QueueConsumer($c, new ChainExtension([
    new LoggerExtension($logger),
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
//        $result = JobResult::createFor(Job::STATUS_COMPLETED);
//    } else {
//        $result = JobResult::createFor(Job::STATUS_FAILED);
//    }
    
    $jobResultMessage = createSubTasks($job, $context, $data);
//    $jobResultMessage = JobResultMessage::create();
//    $jobResultMessage->setToken($data['token']);
//    $jobResultMessage->setJobId($job->getId());
//    $jobResultMessage->setResult($result);

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