<?php
namespace App\Command;

use App\Async\CreateJob;
use App\Async\Topics;
use App\Infra\Uuid;
use App\Model\GracePeriodPolicy;
use App\Model\JobPattern;
use App\Model\RetryFailedPolicy;
use Enqueue\Client\ProducerInterface;
use function Makasim\Values\set_value;
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
        $jobPattern = JobPattern::create();
        $jobPattern->setName('testJob');
        $jobPattern->setUid(Uuid::generate());
        $jobPattern->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
        set_value($jobPattern, 'enqueue.queue', 'demo_job');

        $retryFailedPolicy = RetryFailedPolicy::create();
        $retryFailedPolicy->setRetryLimit(5);
        $jobPattern->addPolicy($retryFailedPolicy);

        $gracePeriodPolicy = GracePeriodPolicy::create();
        $gracePeriodPolicy->setPeriodEndsAt(new \DateTime('now + 30 seconds'));
        $jobPattern->addPolicy($gracePeriodPolicy);

        /** @var ProducerInterface $producer */
        $producer = $this->container->get('enqueue.producer');

        $message = CreateJob::create();
        $message->setJobPattern($jobPattern);

        $producer->send(Topics::CREATE_JOB, $message);

        $output->writeln('');
    }
}