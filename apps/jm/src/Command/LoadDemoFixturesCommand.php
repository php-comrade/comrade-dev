<?php
namespace App\Command;

use App\Commands;
use App\Infra\Uuid;
use Comrade\Shared\Message\CreateJob;
use Comrade\Shared\Model\CronTrigger;
use Comrade\Shared\Model\ExclusivePolicy;
use Comrade\Shared\Model\GracePeriodPolicy;
use Comrade\Shared\Model\HttpRunner;
use Comrade\Shared\Model\JobTemplate;
use Comrade\Shared\Model\NowTrigger;
use Comrade\Shared\Model\QueueRunner;
use Comrade\Shared\Model\RetryFailedPolicy;
use Comrade\Shared\Model\RunSubJobsPolicy;
use Comrade\Shared\Model\Trigger;
use Enqueue\Client\ProducerInterface;
use Makasim\Yadm\Registry;
use MongoDB\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadDemoFixturesCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private $trigger;

    protected function configure()
    {
        $this
            ->setName('comrade:load-demo-fixtures')
            ->addOption('drop', null, InputOption::VALUE_NONE)
            ->addOption('trigger', null, InputOption::VALUE_REQUIRED, '', 'cron')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('drop')) {
            $this->getMongoDbClient()->dropDatabase($this->container->getParameter('mongo_database'));
        }

        $this->trigger = $input->getOption('trigger');

        $this->createDemoSuccessJob();
        $this->createDemoFailedJob();
        $this->createDemoRetryJob();
        $this->createDemoExclusiveJob();
        $this->createDemoTimeoutedJob();
        $this->createDemoJobWithSubJobs();
        $this->createDemoHttpRunnerJob();
    }

    private function createDemoSuccessJob()
    {
        $template = JobTemplate::create();
        $template->setName('demo_success_job');
        $template->setTemplateId(Uuid::generate());
        $template->setProcessTemplateId(Uuid::generate());
        $template->setRunner(QueueRunner::createFor('demo_success_job'));
        $template->addTrigger($this->createTrigger());

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(20);
        $template->setGracePeriodPolicy($policy);

        $this->getProducer()->sendCommand(Commands::CREATE_JOB, CreateJob::createFor($template));
    }

    private function createDemoFailedJob()
    {
        $template = JobTemplate::create();
        $template->setName('demo_failed_job');
        $template->setTemplateId(Uuid::generate());
        $template->setProcessTemplateId(Uuid::generate());
        $template->setRunner(QueueRunner::createFor('demo_failed_job'));
        $template->addTrigger($this->createTrigger());

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(20);
        $template->setGracePeriodPolicy($policy);

        $this->getProducer()->sendCommand(Commands::CREATE_JOB, CreateJob::createFor($template));
    }

    private function createDemoRetryJob()
    {
        $template = JobTemplate::create();
        $template->setName('demo_retry_job');
        $template->setTemplateId(Uuid::generate());
        $template->setProcessTemplateId(Uuid::generate());
        $template->setRunner(QueueRunner::createFor('demo_success_on_third_attempt'));
        $template->addTrigger($this->createTrigger());

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(20);
        $template->setGracePeriodPolicy($policy);

        $policy = RetryFailedPolicy::create();
        $policy->setRetryLimit(5);
        $template->setRetryFailedPolicy($policy);

        $this->getProducer()->sendCommand(Commands::CREATE_JOB, CreateJob::createFor($template));
    }

    private function createDemoExclusiveJob()
    {
        $template = JobTemplate::create();
        $template->setName('demo_exclusive_job');
        $template->setTemplateId(Uuid::generate());
        $template->setProcessTemplateId(Uuid::generate());
        $template->setRunner(QueueRunner::createFor('demo_success_job'));
        $template->addTrigger($this->createTrigger());

        $policy = ExclusivePolicy::create();
        $policy->setOnFailedSubJob(ExclusivePolicy::MARK_JOB_AS_CANCELED);
        $template->setExclusivePolicy($policy);

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(20);
        $template->setGracePeriodPolicy($policy);

        $this->getProducer()->sendCommand(Commands::CREATE_JOB, CreateJob::createFor($template));
    }

    private function createDemoTimeoutedJob()
    {
        $template = JobTemplate::create();
        $template->setName('demo_timeouted_job');
        $template->setTemplateId(Uuid::generate());
        $template->setProcessTemplateId(Uuid::generate());
        $template->setRunner(QueueRunner::createFor('no_one_consumes_from_this_queue'));
        $template->addTrigger($this->createTrigger());

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(5);
        $template->setGracePeriodPolicy($policy);

        $this->getProducer()->sendCommand(Commands::CREATE_JOB, CreateJob::createFor($template));
    }

    private function createDemoJobWithSubJobs()
    {
        $template = JobTemplate::create();
        $template->setName('demo_job_with_sub_jobs');
        $template->setTemplateId(Uuid::generate());
        $template->setProcessTemplateId(Uuid::generate());
        $template->setRunner(QueueRunner::createFor('demo_run_sub_tasks'));
        $template->addTrigger($this->createTrigger());

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(5);
        $template->setGracePeriodPolicy($policy);

        $policy = RunSubJobsPolicy::create();
        $policy->setOnFailedSubJob(RunSubJobsPolicy::MARK_JOB_AS_FAILED);
        $template->setRunSubJobsPolicy($policy);

        $this->getProducer()->sendCommand(Commands::CREATE_JOB, CreateJob::createFor($template));
    }

    private function createTrigger(): Trigger
    {
        if ($this->trigger == 'now') {
            return NowTrigger::create();
        }

        if ($this->trigger == 'cron') {
            $trigger = CronTrigger::create();
            $trigger->setStartAt(new \DateTime('now'));
            $trigger->setMisfireInstruction(CronTrigger::MISFIRE_INSTRUCTION_FIRE_ONCE_NOW);
            $trigger->setExpression(sprintf('*/%d * * * *', rand(5, 10)));

            return $trigger;
        }

        throw new \LogicException(sprintf('The trigger "%s" is not supported are "now", "cron"', $this->trigger));
    }

    private function createDemoHttpRunnerJob()
    {
        $template = JobTemplate::create();
        $template->setName('demo_http_runner_job');
        $template->setTemplateId(Uuid::generate());
        $template->setProcessTemplateId(Uuid::generate());

        $runner = HttpRunner::createFor('http://jmd/demo_success_job');
        $template->setRunner($runner);

        $template->addTrigger($this->createTrigger());

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(20);
        $template->setGracePeriodPolicy($policy);

        $this->getProducer()->sendCommand(Commands::CREATE_JOB, CreateJob::createFor($template));
    }

    private function getProducer(): ProducerInterface
    {
        return $this->container->get(ProducerInterface::class);
    }

    private function getMongoDbClient(): Client
    {
        return $this->container->get('yadm.client');
    }

    private function getYadmRegistry(): Registry
    {
        return $this->container->get('yadm');
    }
}