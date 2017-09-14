<?php
namespace DemoApp;

use App\Async\Commands;
use App\Async\RunSubJobsResult;
use App\Async\RunJob;
use App\ClientQueueRunner;
use App\Infra\Uuid;
use App\Infra\Yadm\ObjectBuilderHook;
use App\JobStatus;
use App\Model\Job;
use App\Async\JobResult as JobResultMessage;
use App\Model\JobResult;
use App\Model\JobResultMetrics;
use App\Model\JobTemplate;
use App\Model\QueueRunner;
use App\Model\SubJobTemplate;
use App\CollectMetrics;
use App\Model\Throwable;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Consumption\Extension\LoggerExtension;
use Enqueue\Consumption\Extension\SignalExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Result;
use function Enqueue\dsn_to_context;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Enqueue\Util\JSON;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\register_cast_hooks;
use function Makasim\Values\register_object_hooks;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__.'/../vendor/autoload.php';


$output = new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG);
$logger = new ConsoleLogger($output);

register_cast_hooks();
register_object_hooks();

(new ObjectBuilderHook([
    Job::SCHEMA => Job::class,
    JobResult::SCHEMA => JobResult::class,
    JobResultMetrics::SCHEMA => JobResultMetrics::class,
    RunJob::SCHEMA => RunJob::class,
    JobResultMessage::SCHEMA => JobResultMessage::class,
    RunSubJobsResult::SCHEMA => RunSubJobsResult::class,
    JobTemplate::SCHEMA => JobTemplate::class,
    SubJobTemplate::SCHEMA => SubJobTemplate::class,
    RunSubJobsResult::SCHEMA => RunSubJobsResult::class,
    QueueRunner::SCHEMA => QueueRunner::class,
    Throwable::SCHEMA => Throwable::class,
]))->register();

/** @var \Enqueue\AmqpExt\AmqpContext $c */
$c = dsn_to_context(getenv('ENQUEUE_DSN'));

$runner = new ClientQueueRunner($c);

foreach (['demo_success_job', 'demo_failed_job', 'demo_failed_with_exception_job', 'demo_success_sub_job', 'demo_run_sub_tasks', 'demo_random_job', 'demo_success_on_third_attempt'] as $queueName) {
    $q = $c->createQueue($queueName);
    $q->addFlag(AMQP_DURABLE);
    $c->declareQueue($q);
}

$queueConsumer = new QueueConsumer($c, new ChainExtension([
    new LoggerExtension($logger),
    new SignalExtension(),
    new LimitConsumptionTimeExtension(new \DateTime('now + 5 minutes')),
]), 0, 200);

$queueConsumer->bind('demo_success_job', function(PsrMessage $message) use ($runner) {
    $runner->run($message, function(RunJob $runJob) {
        do_something_important(rand(2, 6));

        $jobResultMessage = JobResultMessage::create();
        $jobResultMessage->setResult(JobResult::createFor(JobStatus::STATUS_COMPLETED));

        return $jobResultMessage;
    });

    return Result::ACK;
});

$queueConsumer->bind('demo_success_on_third_attempt', function(PsrMessage $message) use ($runner) {
    $runner->run($message, function(RunJob $runJob) {
        $result = JobResult::createFor(JobStatus::STATUS_FAILED);
        if (get_value($runJob->getJob(), 'retryAttempts') > 2) {
            $result = JobResult::createFor(JobStatus::STATUS_COMPLETED);
        }

        do_something_important(rand(2, 6));

        $jobResultMessage = JobResultMessage::create();
        $jobResultMessage->setResult($result);

        return $jobResultMessage;
    });

    return Result::ACK;
});

$queueConsumer->bind('demo_random_job', function(PsrMessage $message) use ($runner) {
    $runner->run($message, function(RunJob $runJob) {
        $statuses = [JobStatus::STATUS_FAILED, JobStatus::STATUS_COMPLETED, JobStatus::STATUS_COMPLETED, JobStatus::STATUS_COMPLETED, JobStatus::STATUS_COMPLETED, JobStatus::STATUS_COMPLETED];
        $result = JobResult::createFor($statuses[rand(0, 3)]);

        do_something_important(rand(2, 6));

        $jobResultMessage = JobResultMessage::create();
        $jobResultMessage->setResult($result);

        return $jobResultMessage;
    });

    return Result::ACK;
});

$queueConsumer->bind('demo_run_sub_tasks', function(PsrMessage $message) use ($runner) {
    $runner->run($message, function(RunJob $runJob) {
        $result = JobResult::createFor(JobStatus::STATUS_RUN_SUB_JOBS);
        $jobResultMessage = RunSubJobsResult::create();
        $jobResultMessage->setProcessTemplateId(Uuid::generate());
        $jobResultMessage->setResult($result);

        $jobTemplate = JobTemplate::create();
        $jobTemplate->setName('testSubJob1');
        $jobTemplate->setTemplateId(Uuid::generate());
        $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
        $jobTemplate->setRunner(QueueRunner::createFor('demo_random_job'));
        $jobResultMessage->addJobTemplate($jobTemplate);

        $jobTemplate = JobTemplate::create();
        $jobTemplate->setName('testSubJob2');
        $jobTemplate->setTemplateId(Uuid::generate());
        $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
        $jobTemplate->setRunner(QueueRunner::createFor('demo_random_job'));
        $jobResultMessage->addJobTemplate($jobTemplate);

        $jobTemplate = JobTemplate::create();
        $jobTemplate->setName('testSubJob3');
        $jobTemplate->setTemplateId(Uuid::generate());
        $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
        $jobTemplate->setRunner(QueueRunner::createFor('demo_random_job'));
        $jobResultMessage->addJobTemplate($jobTemplate);

        $jobTemplate = JobTemplate::create();
        $jobTemplate->setName('testSubJob4');
        $jobTemplate->setTemplateId(Uuid::generate());
        $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
        $jobTemplate->setRunner(QueueRunner::createFor('demo_random_job'));
        $jobResultMessage->addJobTemplate($jobTemplate);

        do_something_important(rand(2, 6));

        return $jobResultMessage;
    });

    return Result::ACK;
});

$queueConsumer->bind('demo_failed_job', function(PsrMessage $message) use ($runner) {
    $runner->run($message, function(RunJob $runJob) {
        do_something_important(rand(2, 6));

        $jobResultMessage = JobResultMessage::create();
        $jobResultMessage->setResult(JobResult::createFor(JobStatus::STATUS_FAILED));

        return $jobResultMessage;
    });

    return Result::ACK;
});

$queueConsumer->bind('demo_failed_with_exception_job', function(PsrMessage $message) use ($runner) {
    $runner->run($message, function(RunJob $runJob) {
        do_something_important(rand(2, 6));

        throw new \LogicException('Something went wrong');
    });

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


function do_something_important($timeout)
{
    $limit = microtime(true) + $timeout;

    $memoryConsumed = false;
    
    while (microtime(true) < $limit) {

        if ($memoryConsumed) {
            foreach (range(1000000, 5000000) as $index) {
                $arr[] = $index;
            }

            $memoryConsumed = true;
        }

        usleep(10000);
    }
}
