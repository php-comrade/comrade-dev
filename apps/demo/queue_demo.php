<?php
namespace DemoApp;

use Comrade\Client\ClientQueueRunner;
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
use Interop\Amqp\AmqpContext;
use Interop\Queue\PsrMessage;
use function Makasim\Values\get_value;
use function Makasim\Values\register_cast_hooks;
use function Makasim\Values\register_object_hooks;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__.'/vendor/autoload.php';


$output = new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG);
$logger = new ConsoleLogger($output);

register_cast_hooks();
register_object_hooks();

wait_for_broker($logger, getenv('ENQUEUE_DSN'));

/** @var AmqpContext $c */
$c = dsn_to_context(getenv('ENQUEUE_DSN'));

$runner = new ClientQueueRunner($c);

foreach (['demo_success_job', 'demo_failed_job', 'demo_failed_with_exception_job', 'demo_success_with_result', 'demo_run_sub_tasks', 'demo_random_job', 'demo_success_on_third_attempt', 'demo_dependent_job', 'demo_second_dependent_job'] as $queueName) {
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
        do_something_important(rand(200, 1000));

        return JobAction::COMPLETE;
    });

    return Result::ACK;
});

$queueConsumer->bind('demo_success_with_result', function(PsrMessage $message) use ($runner) {
    $runner->run($message, function(RunJob $runJob) {
        do_something_important(rand(200, 1000));

        return $runJob->getJob()->getId().'_'.uniqid();
    });

    return Result::ACK;
});

$queueConsumer->bind('demo_success_on_third_attempt', function(PsrMessage $message) use ($runner) {
    $runner->run($message, function(RunJob $runJob) {
        do_something_important(rand(200, 1000));

        return get_value($runJob->getJob(), 'retryFailedPolicy.retryAttempts') > 2 ? JobAction::COMPLETE : JobAction::FAIL;
    });

    return Result::ACK;
});

$queueConsumer->bind('demo_random_job', function(PsrMessage $message) use ($runner) {
    $runner->run($message, function(RunJob $runJob) {
        do_something_important(rand(200, 1000));

        $actions = [JobAction::FAIL, JobAction::COMPLETE, JobAction::COMPLETE, JobAction::COMPLETE, JobAction::COMPLETE, JobAction::COMPLETE, JobAction::COMPLETE];

        return $actions[rand(0, count($actions) - 1)];
    });

    return Result::ACK;
});

$queueConsumer->bind('demo_run_sub_tasks', function(PsrMessage $message) use ($runner) {
    $runner->run($message, function(RunJob $runJob) {
        do_something_important(rand(200, 1000));

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
        do_something_important(rand(200, 1000));

        return JobAction::FAIL;
    });

    return Result::ACK;
});

$queueConsumer->bind('demo_dependent_job', function(PsrMessage $message) use ($runner) {
    $runner->run($message, function(RunJob $runJob) {
        do_something_important(rand(200, 1000));

        return ['first' => ['foo' => 'fooVal']];
    });

    return Result::ACK;
});

$queueConsumer->bind('demo_second_dependent_job', function(PsrMessage $message) use ($runner) {
    $runner->run($message, function(RunJob $runJob) {
        do_something_important(rand(200, 1000));

        if (false == is_array($runJob->getJob()->getPayload())) {
            return JobAction::FAIL;
        }

        return array_replace($runJob->getJob()->getPayload(), ['second' => ['bar' => 'barVal']]);
    });

    return Result::ACK;
});

$queueConsumer->bind('demo_failed_with_exception_job', function(PsrMessage $message) use ($runner) {
    try {
        $runner->run($message, function (RunJob $runJob) {
            do_something_important(rand(200, 1000));

            throw new \LogicException('Something went wrong');
        });
    } catch (\LogicException $e) {}

    return Result::ACK;
});

$queueConsumer->consume();

function do_something_important($timeout)
{
    $limit = microtime(true) + ($timeout / 1000);

    $memoryConsumed = false;
    
    while (microtime(true) < $limit) {
        if ($memoryConsumed) {
            foreach (range(1000000, 5000000) as $index) {
                $arr[] = $index;
            }

            $memoryConsumed = true;
        }
    }
}

function wait_for_broker(LoggerInterface $logger, $brokerDsn)
{
    $fp = null;
    $limit = time() + 20;
    $host = parse_url($brokerDsn, PHP_URL_HOST);
    $port = parse_url($brokerDsn, PHP_URL_PORT);

    try {
        do {
            $fp = @fsockopen($host, $port);

            if (false == is_resource($fp)) {
                $logger->debug(sprintf('service is not running %s:%s', $host, $port));
                sleep(1);
            }
        } while (false == is_resource($fp) || $limit < time());

        if (false == $fp) {
            throw new \LogicException(sprintf('Failed to connect to "%s:%s"', $host, $port));
        }

        $logger->debug(sprintf('service is online %s:%s', $host, $port));
    } finally {
        if (is_resource($fp)) {
            fclose($fp);
        }
    }
}
