<?php
namespace App\Command;

use App\Async\CreateJob;
use App\Async\CreateSubJobs;
use App\Async\Topics;
use App\Infra\Uuid;
use App\Model\GracePeriodPolicy;
use App\Model\JobTemplate;
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
        $jobTemplate = JobTemplate::create();
        $jobTemplate->setName('testJob');
        $jobTemplate->setTemplateId(Uuid::generate());
        $jobTemplate->setProcessTemplateId(Uuid::generate());
        $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
        set_value($jobTemplate, 'enqueue.queue', 'demo_job');

        $retryFailedPolicy = RetryFailedPolicy::create();
        $retryFailedPolicy->setRetryLimit(5);
        $jobTemplate->addPolicy($retryFailedPolicy);

        $gracePeriodPolicy = GracePeriodPolicy::create();
        $gracePeriodPolicy->setPeriodEndsAt(new \DateTime('now + 30 seconds'));
        $jobTemplate->addPolicy($gracePeriodPolicy);

        /** @var ProducerInterface $producer */
        $producer = $this->container->get('enqueue.producer');

        $message = CreateJob::create();
        $message->setJobTemplate($jobTemplate);

        $producer->send(Topics::CREATE_JOB, $message);
//
//        sleep(2);
//
//        $createSubJobs = CreateSubJobs::create();
//        $createSubJobs->setParentJobUid($jobTemplate->getId());
//        // TODO
//        $createSubJobs->setParentProcessUid(Uuid::generate());
//
//        $jobTemplate = JobTemplate::create();
//        $jobTemplate->setName('testSubJob1');
//        $jobTemplate->setId(Uuid::generate());
//        $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
//        set_value($jobTemplate, 'enqueue.queue', 'demo_sub_job');
//        $createSubJobs->addSubJobTemplates($jobTemplate);
//
//        $jobTemplate = JobTemplate::create();
//        $jobTemplate->setName('testSubJob2');
//        $jobTemplate->setId(Uuid::generate());
//        $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
//        set_value($jobTemplate, 'enqueue.queue', 'demo_sub_job');
//        $createSubJobs->addSubJobTemplates($jobTemplate);
//
//        $jobTemplate = JobTemplate::create();
//        $jobTemplate->setName('testSubJob3');
//        $jobTemplate->setId(Uuid::generate());
//        $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
//        set_value($jobTemplate, 'enqueue.queue', 'demo_sub_job');
//        $createSubJobs->addSubJobTemplates($jobTemplate);
//
//        $jobTemplate = JobTemplate::create();
//        $jobTemplate->setName('testSubJob4');
//        $jobTemplate->setId(Uuid::generate());
//        $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);
//        set_value($jobTemplate, 'enqueue.queue', 'demo_sub_job');
//        $createSubJobs->addSubJobTemplates($jobTemplate);
//
//        $producer->send(Topics::CREATE_SUB_JOBS, $createSubJobs);

        $output->writeln('');
    }
}