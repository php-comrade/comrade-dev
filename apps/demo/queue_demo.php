<?php
namespace DemoApp;

use Comrade\Client\ClientQueueRunner;
use Comrade\Shared\ComradeClassMap;
use Comrade\Shared\Message\RunnerResult;
use Comrade\Shared\Message\Part\SubJob;
use Comrade\Shared\Message\RunJob;
use Comrade\Shared\Model\JobAction;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Consumption\Extension\LoggerExtension;
use Enqueue\Consumption\Extension\SignalExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Result;
use function Enqueue\dsn_to_context;
use Interop\Queue\PsrMessage;
use function Makasim\Values\get_value;
use function Makasim\Values\register_cast_hooks;
use function Makasim\Values\register_global_hook;
use function Makasim\Values\register_object_hooks;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__.'/vendor/autoload.php';


$output = new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG);
$logger = new ConsoleLogger($output);

register_cast_hooks();
register_object_hooks();

register_global_hook('get_object_class', function(array $values) {
    if (isset($values['schema'])) {
        $classMap = (new ComradeClassMap())->get();
        if (false == array_key_exists($values['schema'], $classMap)) {
            throw new \LogicException(sprintf('An object has schema set "%s" but there is no class for it', $values['schema']));
        }

        return $classMap[$values['schema']];
    }
});

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

        return JobAction::COMPLETE;
    });

    return Result::ACK;
});

$queueConsumer->bind('demo_success_on_third_attempt', function(PsrMessage $message) use ($runner) {
    $runner->run($message, function(RunJob $runJob) {
        do_something_important(rand(2, 6));

        return get_value($runJob->getJob(), 'retryFailedPolicy.retryAttempts') > 2 ? JobAction::COMPLETE : JobAction::FAIL;
    });

    return Result::ACK;
});

$queueConsumer->bind('demo_random_job', function(PsrMessage $message) use ($runner) {
    $runner->run($message, function(RunJob $runJob) {
        do_something_important(rand(2, 6));

        $actions = [JobAction::FAIL, JobAction::COMPLETE, JobAction::COMPLETE, JobAction::COMPLETE, JobAction::COMPLETE, JobAction::COMPLETE, JobAction::COMPLETE];

        return $actions[rand(0, count($actions) - 1)];
    });

    return Result::ACK;
});

$queueConsumer->bind('demo_run_sub_tasks', function(PsrMessage $message) use ($runner) {
    $runner->run($message, function(RunJob $runJob) {
        do_something_important(rand(2, 6));

        $message = RunnerResult::createFor($runJob, JobAction::RUN_SUB_JOBS);

        $message->addSubJob(SubJob::createFor('demo_sub_job' , ['foo' => 'fooVal']));
        $message->addSubJob(SubJob::createFor('demo_sub_job' , ['bar' => 'barVal']));
        $message->addSubJob(SubJob::createFor('demo_sub_job' , ['baz' => 'bazVal']));
        $message->addSubJob(SubJob::createFor('demo_sub_job' , ['ololo' => 'ololoVal']));

        return $message;
    });

    return Result::ACK;
});

$queueConsumer->bind('demo_failed_job', function(PsrMessage $message) use ($runner) {
    $runner->run($message, function(RunJob $runJob) {
        do_something_important(rand(2, 6));

        return JobAction::FAIL;
    });

    return Result::ACK;
});

$queueConsumer->bind('demo_failed_with_exception_job', function(PsrMessage $message) use ($runner) {
    try {
        $runner->run($message, function (RunJob $runJob) {
            do_something_important(rand(2, 6));

            throw new \LogicException('Something went wrong');
        });
    } catch (\LogicException $e) {}

    return Result::ACK;
});

$queueConsumer->consume();

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
