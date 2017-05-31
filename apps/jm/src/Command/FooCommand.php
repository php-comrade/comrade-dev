<?php
namespace App\Command;

use App\Async\CreateJob;
use App\Async\Topics;
use App\Infra\Uuid;
use App\Model\Job;
use Enqueue\Client\ProducerInterface;
use Formapro\Pvm\BehaviorRegistry;
use Formapro\Pvm\DefaultBehaviorRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class FooCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected function configure()
    {
        $this->setName('foo');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $job = Job::create();
        $job->setName('testJob');
        $job->setUid(Uuid::generate());
        $job->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);


        $producer = $this->container->get('enqueue.producer');

        $message = CreateJob::create();
        $message->setJob($job);

        $producer->send(Topics::CREATE_JOB, $message);
    }
}