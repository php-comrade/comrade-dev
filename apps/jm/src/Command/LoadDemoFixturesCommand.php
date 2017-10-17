<?php
namespace App\Command;

use App\Commands;
use App\Infra\Uuid;
use Comrade\Shared\Message\CreateJob;
use Comrade\Shared\Model\CronTrigger;
use Comrade\Shared\Model\ExclusivePolicy;
use Comrade\Shared\Model\GracePeriodPolicy;
use Comrade\Shared\Model\HttpRunner;
use App\Model\JobTemplate;
use Comrade\Shared\Model\NowTrigger;
use Comrade\Shared\Model\QueueRunner;
use Comrade\Shared\Model\RetryFailedPolicy;
use Comrade\Shared\Model\RunSubJobsPolicy;
use Comrade\Shared\Model\SubJobPolicy;
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
        $template->setRunner(QueueRunner::createFor('demo_success_job'));

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(20);
        $template->setGracePeriodPolicy($policy);

        $createJob = CreateJob::createFor($template);
        $this->createTrigger($createJob);

        $this->getProducer()->sendCommand(Commands::CREATE_JOB, $createJob);
    }

    private function createDemoFailedJob()
    {
        $template = JobTemplate::create();
        $template->setName('demo_failed_job');
        $template->setTemplateId(Uuid::generate());
        $template->setRunner(QueueRunner::createFor('demo_failed_with_exception_job'));

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(20);
        $template->setGracePeriodPolicy($policy);

        $createJob = CreateJob::createFor($template);
        $this->createTrigger($createJob);

        $this->getProducer()->sendCommand(Commands::CREATE_JOB, $createJob);
    }

    private function createDemoRetryJob()
    {
        $template = JobTemplate::create();
        $template->setName('demo_retry_job');
        $template->setTemplateId(Uuid::generate());
        $template->setRunner(QueueRunner::createFor('demo_success_on_third_attempt'));

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(60);
        $template->setGracePeriodPolicy($policy);

        $policy = RetryFailedPolicy::create();
        $policy->setRetryLimit(5);
        $template->setRetryFailedPolicy($policy);

        $createJob = CreateJob::createFor($template);
        $this->createTrigger($createJob);

        $this->getProducer()->sendCommand(Commands::CREATE_JOB, $createJob);
    }

    private function createDemoExclusiveJob()
    {
        $template = JobTemplate::create();
        $template->setName('demo_exclusive_job');
        $template->setTemplateId(Uuid::generate());
        $template->setRunner(QueueRunner::createFor('demo_success_job'));

        $policy = ExclusivePolicy::create();
        $policy->setOnDuplicateRun(ExclusivePolicy::MARK_JOB_AS_CANCELED);
        $template->setExclusivePolicy($policy);

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(20);
        $template->setGracePeriodPolicy($policy);

        $createJob = CreateJob::createFor($template);
        $this->createTrigger($createJob);

        $this->getProducer()->sendCommand(Commands::CREATE_JOB, $createJob);
    }

    private function createDemoTimeoutedJob()
    {
        $template = JobTemplate::create();
        $template->setName('demo_timeouted_job');
        $template->setTemplateId(Uuid::generate());
        $template->setRunner(QueueRunner::createFor('no_one_consumes_from_this_queue'));

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(5);
        $template->setGracePeriodPolicy($policy);

        $createJob = CreateJob::createFor($template);
        $this->createTrigger($createJob);

        $this->getProducer()->sendCommand(Commands::CREATE_JOB, $createJob);
    }

    private function createDemoJobWithSubJobs()
    {
        $parentTemplate = JobTemplate::create();
        $parentTemplate->setName('demo_job_with_sub_jobs');
        $parentTemplate->setTemplateId(Uuid::generate());
        $parentTemplate->setRunner(QueueRunner::createFor('demo_run_sub_tasks'));

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(300);
        $parentTemplate->setGracePeriodPolicy($policy);

        $policy = RunSubJobsPolicy::create();
        $policy->setOnFailedSubJob(RunSubJobsPolicy::MARK_JOB_AS_FAILED);
        $parentTemplate->setRunSubJobsPolicy($policy);

        $createJob = CreateJob::createFor($parentTemplate);
        $this->createTrigger($createJob);

        $this->getProducer()->sendCommand(Commands::CREATE_JOB, $createJob);

        $childTemplate = JobTemplate::create();
        $childTemplate->setName('demo_sub_job');
        $childTemplate->setTemplateId(Uuid::generate());
        $childTemplate->setRunner(QueueRunner::createFor('demo_random_job'));

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(20);
        $childTemplate->setGracePeriodPolicy($policy);

        $policy = SubJobPolicy::create();
        $policy->setParentId($parentTemplate->getTemplateId());
        $childTemplate->setSubJobPolicy($policy);

        $createJob = CreateJob::createFor($childTemplate);

        $this->getProducer()->sendCommand(Commands::CREATE_JOB, $createJob);
    }

    private function createDemoHttpRunnerJob()
    {
        $template = JobTemplate::create();
        $template->setName('demo_http_runner_job');
        $template->setTemplateId(Uuid::generate());

        $runner = HttpRunner::createFor('http://jmdh/demo_success_job');
        $template->setRunner($runner);

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(20);
        $template->setGracePeriodPolicy($policy);

        $createJob = CreateJob::createFor($template);
        $this->createTrigger($createJob);

        $this->getProducer()->sendCommand(Commands::CREATE_JOB, $createJob);
    }

    private function createTrigger(CreateJob $createJob): void
    {
        if ($this->trigger == 'none') {
            return;
        }

        if ($this->trigger == 'now') {
            $trigger = NowTrigger::create();
            $trigger->setTemplateId($createJob->getJobTemplate()->getTemplateId());

            $createJob->addTrigger($trigger);

            return;
        }

        if ($this->trigger == 'cron') {
            $trigger = CronTrigger::create();
            $trigger->setTemplateId($createJob->getJobTemplate()->getTemplateId());
            $trigger->setStartAt(new \DateTime('now'));
            $trigger->setMisfireInstruction(CronTrigger::MISFIRE_INSTRUCTION_FIRE_ONCE_NOW);
            $trigger->setExpression(sprintf('*/%d * * * *', rand(5, 10)));

            $createJob->addTrigger($trigger);

            return;
        }

        throw new \LogicException(sprintf('The trigger "%s" is not supported are "now", "cron", "none"', $this->trigger));
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