<?php
namespace DemoApp;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LoggerExtension;
use Enqueue\Consumption\Extension\SignalExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Result;
use function Enqueue\dsn_to_context;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Util\JSON;
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

/** @var \Enqueue\AmqpExt\AmqpContext $c */
$c = dsn_to_context('amqp://guest:guest@rabbitmq:5672/jm');

$queue = $c->createQueue('demo_job');
$queue->addFlag(AMQP_DURABLE);
$c->declareQueue($queue);

$queueConsumer = new QueueConsumer($c, new ChainExtension([
    new LoggerExtension(new EchoLogger()),
    new SignalExtension(),
]));

$queueConsumer->bind($queue, function(PsrMessage $message, PsrContext $context) {
    $data = JSON::decode($message->getBody());

    // processing job

    $data['job']['finished'] = true;
    $data['scheme'] = 'http://jm.forma-pro.com/schemas/message/process-feedback.json';

    $feedbackQueue = $context->createQueue('job_manager_process_feedback');
    $message = $context->createMessage(JSON::encode($data), [
        'enqueue.topic_name' => 'job_manager.process_feedback',
        'enqueue.processor_queue_name' => 'job_manager_process_feedback',
        'enqueue.processor_name' => 'job_manager_process_feedback',
    ]);

    $context->createProducer()->send($feedbackQueue, $message);

    return Result::ACK;
});

$queueConsumer->consume();