<?php
namespace DemoApp;

use App\Async\Commands;
use App\Async\RunSubJobsResult;
use App\Async\RunJob;
use App\Infra\Uuid;
use App\Infra\Yadm\ObjectBuilderHook;
use App\JobStatus;
use App\Model\Job;
use App\Async\JobResult as JobResultMessage;
use App\Model\JobResult;
use App\Model\JobTemplate;
use App\Model\QueueRunner;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LoggerExtension;
use Enqueue\Consumption\Extension\SignalExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Result;
use function Enqueue\dsn_to_context;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Enqueue\Util\JSON;
use function Makasim\Values\get_values;
use function Makasim\Values\register_cast_hooks;
use function Makasim\Values\register_object_hooks;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__.'/../jm/vendor/autoload.php';


$output = new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG);
$logger = new ConsoleLogger($output);

register_cast_hooks();
register_object_hooks();

(new ObjectBuilderHook([
    Job::SCHEMA => Job::class,
    JobResult::SCHEMA => JobResult::class,
    RunJob::SCHEMA => RunJob::class,
    JobResultMessage::SCHEMA => JobResultMessage::class,
    RunSubJobsResult::SCHEMA => RunSubJobsResult::class,
    JobTemplate::SCHEMA => JobTemplate::class,
    RunSubJobsResult::SCHEMA => RunSubJobsResult::class,
]))->register();

/** @var \Enqueue\AmqpExt\AmqpContext $c */
$c = dsn_to_context('amqp://guest:guest@rabbitmq:5672/jm?pre_fetch_count=1&receive_method=basic_consume');

foreach (['demo_success_job', 'demo_failed_job', 'demo_success_sub_job', 'demo_run_sub_tasks', 'demo_intermediate_status', 'demo_random_job'] as $queueName) {
    $q = $c->createQueue($queueName);
    $q->addFlag(AMQP_DURABLE);
    $c->declareQueue($q);
}

$queueConsumer = new QueueConsumer($c, new ChainExtension([
    new LoggerExtension($logger),
    new SignalExtension(),
]));

$queueConsumer->bind('demo_success_job', function(PsrMessage $message, PsrContext $context) {
    if ($message->isRedelivered()) {
        return Result::reject('The message was redelivered. Reject it');
    }

    $runJob = RunJob::create(JSON::decode($message->getBody()));

    $result = JobResult::createFor(JobStatus::STATUS_COMPLETED);

    sleep(10);

    send_result(convert($runJob, $result));

    return Result::ACK;
});

$queueConsumer->bind('demo_random_job', function(PsrMessage $message, PsrContext $context) {
    if ($message->isRedelivered()) {
        return Result::reject('The message was redelivered. Reject it');
    }

    $runJob = RunJob::create(JSON::decode($message->getBody()));

    $statuses = [JobStatus::STATUS_FAILED, JobStatus::STATUS_COMPLETED, JobStatus::STATUS_COMPLETED, JobStatus::STATUS_COMPLETED];
    $result = JobResult::createFor($statuses[rand(0, 3)]);

    sleep(10);

    send_result(convert($runJob, $result));

    return Result::ACK;
});

$queueConsumer->bind('demo_run_sub_tasks', function(PsrMessage $message, PsrContext $context) {
    if ($message->isRedelivered()) {
        return Result::reject('The message was redelivered. Reject it');
    }

    $runJob = RunJob::create(JSON::decode($message->getBody()));

    $result = JobResult::createFor(JobStatus::STATUS_RUN_SUB_JOBS);

    $jobResultMessage = convert($runJob, $result);
    $jobResultMessage = RunSubJobsResult::create(get_values($jobResultMessage));

    $jobTemplate = JobTemplate::create();
    $jobTemplate->setName('testSubJob1');
    $jobTemplate->setTemplateId(Uuid::generate());
    $jobTemplate->setProcessTemplateId(Uuid::generate());
    $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
    $jobTemplate->setRunner(QueueRunner::createFor('demo_random_job'));
    $jobResultMessage->addJobTemplate($jobTemplate);

    $jobTemplate = JobTemplate::create();
    $jobTemplate->setName('testSubJob2');
    $jobTemplate->setTemplateId(Uuid::generate());
    $jobTemplate->setProcessTemplateId(Uuid::generate());
    $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
    $jobTemplate->setRunner(QueueRunner::createFor('demo_random_job'));
    $jobResultMessage->addJobTemplate($jobTemplate);

    $jobTemplate = JobTemplate::create();
    $jobTemplate->setName('testSubJob3');
    $jobTemplate->setTemplateId(Uuid::generate());
    $jobTemplate->setProcessTemplateId(Uuid::generate());
    $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
    $jobTemplate->setRunner(QueueRunner::createFor('demo_random_job'));
    $jobResultMessage->addJobTemplate($jobTemplate);

    $jobTemplate = JobTemplate::create();
    $jobTemplate->setName('testSubJob4');
    $jobTemplate->setTemplateId(Uuid::generate());
    $jobTemplate->setProcessTemplateId(Uuid::generate());
    $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
    $jobTemplate->setRunner(QueueRunner::createFor('demo_random_job'));
    $jobResultMessage->addJobTemplate($jobTemplate);

    sleep(5);

    send_result($jobResultMessage);

    return Result::ACK;
});

$queueConsumer->bind('demo_failed_job', function(PsrMessage $message, PsrContext $context) {
    if ($message->isRedelivered()) {
        return Result::reject('The message was redelivered. Reject it');
    }

    $runJob = RunJob::create(JSON::decode($message->getBody()));

    $result = JobResult::createFor(JobStatus::STATUS_FAILED);

    sleep(5);

    send_result(convert($runJob, $result));

    return Result::ACK;
});

$queueConsumer->bind('demo_intermediate_status', function(PsrMessage $message, PsrContext $context) {
    if ($message->isRedelivered()) {
        return Result::reject('The message was redelivered. Reject it');
    }

    $runJob = RunJob::create(JSON::decode($message->getBody()));
    $result = JobResult::createFor(JobStatus::STATUS_RUNNING);

    sleep(5);

    send_result(convert($runJob, $result));

    sleep(5);

    $result = JobResult::createFor(JobStatus::STATUS_COMPLETED);
    send_result(convert($runJob, $result));

    return Result::ACK;
});

$queueConsumer->consume();

function send_result(JobResultMessage $message) {
    global $c;

    $c->createProducer()->send(
        $c->createQueue(Commands::JOB_RESULT),
        $c->createMessage(JSON::encode($message))
    );
}

/**
 * @param RunJob $runJob
 * @param JobResult $result
 *
 * @return JobResultMessage
 */
function convert(RunJob $runJob, JobResult $result) {
    $jobResultMessage = JobResultMessage::create();
    $jobResultMessage->setToken($runJob->getToken());
    $jobResultMessage->setJobId($runJob->getJob()->getId());
    $jobResultMessage->setResult($result);

    return $jobResultMessage;
}
