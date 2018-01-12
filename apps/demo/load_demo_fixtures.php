#!/usr/bin/env php
<?php
require __DIR__.'/vendor/autoload.php';

use Comrade\Shared\Message\CreateJob;
use Comrade\Shared\Model\CronTrigger;
use Comrade\Shared\Model\ExclusivePolicy;
use Comrade\Shared\Model\GracePeriodPolicy;
use Comrade\Shared\Model\HttpRunner;
use Comrade\Shared\Model\JobStatus;
use Comrade\Shared\Model\JobTemplate;
use Comrade\Shared\Model\NowTrigger;
use Comrade\Shared\Model\QueueRunner;
use Comrade\Shared\Model\RetryFailedPolicy;
use Comrade\Shared\Model\RunDependentJobPolicy;
use Comrade\Shared\Model\RunSubJobsPolicy;
use Comrade\Shared\Model\SubJobPolicy;
use function Enqueue\dsn_to_context;
use Enqueue\Util\JSON;
use Enqueue\Util\UUID;
use Interop\Amqp\AmqpContext;
use Interop\Queue\PsrContext;
use function Makasim\Values\register_cast_hooks;
use function Makasim\Values\register_object_hooks;
use MongoDB\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Application;

class LoadDemoFixturesCommand extends Command
{
    /**
     * @var string
     */
    private $trigger;

    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @var Client
     */
    private $client;

    public function __construct(PsrContext $context, Client $client)
    {
        $this->context = $context;
        $this->client = $client;

        parent::__construct();
    }

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
        $this->waitForService(getenv('MONGO_DSN'));
        $this->waitForService(getenv('ENQUEUE_DSN'));

        if ($input->getOption('drop')) {
            $db = parse_url(getenv('MONGO_DSN'), PHP_URL_PATH);
            $this->client->dropDatabase(trim($db, '/'));
        }

        $this->trigger = $input->getOption('trigger');

        $this->createDemoSuccessJob();
        $this->createDemoFailedJob();
        $this->createDemoRetryJob();
        $this->createDemoExclusiveJob();
        $this->createDemoTimeoutedJob();
        $this->createDemoJobWithSubJobs();
        $this->createDemoHttpRunnerJob();
        $this->createDemoDependentJobs();
//        $this->createImportDemoJobs();
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

        $this->sendCreateJob($createJob);
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

        $this->sendCreateJob($createJob);
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

        $this->sendCreateJob($createJob);
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

        $this->sendCreateJob($createJob);
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

        $this->sendCreateJob($createJob);
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
        $policy->setResultPayloadKey('subJobs.results');
        $parentTemplate->setRunSubJobsPolicy($policy);

        $createJob = CreateJob::createFor($parentTemplate);
        $this->createTrigger($createJob);

        $this->sendCreateJob($createJob);

        $childTemplate = JobTemplate::create();
        $childTemplate->setName('demo_sub_job');
        $childTemplate->setTemplateId(Uuid::generate());
        $childTemplate->setRunner(QueueRunner::createFor('demo_success_with_result'));

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(20);
        $childTemplate->setGracePeriodPolicy($policy);

        $policy = SubJobPolicy::create();
        $policy->setParentId($parentTemplate->getTemplateId());
        $childTemplate->setSubJobPolicy($policy);

        $createJob = CreateJob::createFor($childTemplate);

        $this->sendCreateJob($createJob);
    }

    private function createDemoDependentJobs()
    {
        $secondTemplate = JobTemplate::create();
        $secondTemplate->setName('demo_second_dependent_job');
        $secondTemplate->setTemplateId(Uuid::generate());
        $secondTemplate->setRunner(QueueRunner::createFor('demo_second_dependent_job'));

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(300);
        $secondTemplate->setGracePeriodPolicy($policy);

        $this->sendCreateJob(CreateJob::createFor($secondTemplate));

        $thirdTemplate = JobTemplate::create();
        $thirdTemplate->setName('demo_third_dependent_job');
        $thirdTemplate->setTemplateId(Uuid::generate());
        $thirdTemplate->setRunner(QueueRunner::createFor('demo_success_job'));

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(300);
        $thirdTemplate->setGracePeriodPolicy($policy);

        $this->sendCreateJob(CreateJob::createFor($thirdTemplate));

        $firstTemplate = JobTemplate::create();
        $firstTemplate->setName('demo_dependent_jobs');
        $firstTemplate->setTemplateId(Uuid::generate());
        $firstTemplate->setRunner(QueueRunner::createFor('demo_dependent_job'));

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(300);
        $firstTemplate->setGracePeriodPolicy($policy);

        $policy = RunDependentJobPolicy::create();
        $policy->setRunAlways(true);
        $policy->setTemplateId($secondTemplate->getTemplateId());
        $firstTemplate->addRunDependentJobPolicy($policy);

        $policy = RunDependentJobPolicy::create();
        $policy->setRunAlways(false);
        $policy->addRunOnStatus(JobStatus::FAILED);
        $policy->setTemplateId($thirdTemplate->getTemplateId());
        $firstTemplate->addRunDependentJobPolicy($policy);

        $createJob = CreateJob::createFor($firstTemplate);
        $this->createTrigger($createJob);

        $this->sendCreateJob($createJob);
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

        $this->sendCreateJob($createJob);
    }

    private function createImportDemoJobs()
    {
        // status

        $statusTemplate = JobTemplate::create();
        $statusTemplate->setName('import_status');
        $statusTemplate->setTemplateId(Uuid::generate());
        $statusTemplate->setRunner(QueueRunner::createFor('import_status'));

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(300);
        $statusTemplate->setGracePeriodPolicy($policy);

        $this->sendCreateJob(CreateJob::createFor($statusTemplate));

        // load

        $loadTemplate = JobTemplate::create();
        $loadTemplate->setName('import_load');
        $loadTemplate->setTemplateId(Uuid::generate());
        $loadTemplate->setRunner(QueueRunner::createFor('import_load'));

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(300);
        $loadTemplate->setGracePeriodPolicy($policy);

        $policy = RunDependentJobPolicy::create();
        $policy->setTemplateId($statusTemplate->getTemplateId());
        $policy->addRunOnStatus(JobStatus::FAILED);
        $policy->addRunOnStatus(JobStatus::CANCELED);
        $policy->addRunOnStatus(JobStatus::TERMINATED);
        $loadTemplate->addRunDependentJobPolicy($policy);

        $policy = RunSubJobsPolicy::create();
        $policy->setResultPayloadKey('loaded_properties');
        $policy->setOnFailedSubJob(RunSubJobsPolicy::MARK_JOB_AS_FAILED);
        $loadTemplate->setRunSubJobsPolicy($policy);

        $this->sendCreateJob(CreateJob::createFor($loadTemplate));

        $loadPropertyTemplate = JobTemplate::create();
        $loadPropertyTemplate->setName('import_load_property');
        $loadPropertyTemplate->setTemplateId(Uuid::generate());
        $loadPropertyTemplate->setRunner(QueueRunner::createFor('import_load_property'));

        $policy = SubJobPolicy::create();
        $policy->setParentId($loadTemplate->getTemplateId());
        $loadPropertyTemplate->setSubJobPolicy($policy);

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(300);
        $loadPropertyTemplate->setGracePeriodPolicy($policy);

        $this->sendCreateJob(CreateJob::createFor($loadPropertyTemplate));

        // transform

        $transformTemplate = JobTemplate::create();
        $transformTemplate->setName('import_transform');
        $transformTemplate->setTemplateId(Uuid::generate());
        $transformTemplate->setRunner(QueueRunner::createFor('import_transform'));

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(300);
        $transformTemplate->setGracePeriodPolicy($policy);

        $policy = RunSubJobsPolicy::create();
        $policy->setResultPayloadKey('transformed_properties');
        $policy->setOnFailedSubJob(RunSubJobsPolicy::MARK_JOB_AS_FAILED);
        $transformTemplate->setRunSubJobsPolicy($policy);

        $policy = RunDependentJobPolicy::create();
        $policy->setTemplateId($statusTemplate->getTemplateId());
        $policy->addRunOnStatus(JobStatus::FAILED);
        $policy->addRunOnStatus(JobStatus::CANCELED);
        $policy->addRunOnStatus(JobStatus::TERMINATED);
        $transformTemplate->addRunDependentJobPolicy($policy);

        $policy = RunDependentJobPolicy::create();
        $policy->setTemplateId($loadTemplate->getTemplateId());
        $policy->addRunOnStatus(JobStatus::COMPLETED);
        $transformTemplate->addRunDependentJobPolicy($policy);

        $this->sendCreateJob(CreateJob::createFor($transformTemplate));

        $transformPropertyTemplate = JobTemplate::create();
        $transformPropertyTemplate->setName('import_transform_property');
        $transformPropertyTemplate->setTemplateId(Uuid::generate());
        $transformPropertyTemplate->setRunner(QueueRunner::createFor('import_transform_property'));

        $policy = SubJobPolicy::create();
        $policy->setParentId($transformTemplate->getTemplateId());
        $transformPropertyTemplate->setSubJobPolicy($policy);

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(300);
        $transformPropertyTemplate->setGracePeriodPolicy($policy);

        $this->sendCreateJob(CreateJob::createFor($transformPropertyTemplate));

        // extract

        $extractTemplate = JobTemplate::create();
        $extractTemplate->setName('import_extract');
        $extractTemplate->setTemplateId(Uuid::generate());
        $extractTemplate->setRunner(QueueRunner::createFor('import_extract'));

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(300);
        $extractTemplate->setGracePeriodPolicy($policy);

        $policy = RunSubJobsPolicy::create();
        $policy->setResultPayloadKey('extracted_properties');
        $policy->setOnFailedSubJob(RunSubJobsPolicy::MARK_JOB_AS_FAILED);
        $extractTemplate->setRunSubJobsPolicy($policy);

        $policy = RunDependentJobPolicy::create();
        $policy->setTemplateId($transformTemplate->getTemplateId());
        $policy->addRunOnStatus(JobStatus::COMPLETED);
        $extractTemplate->addRunDependentJobPolicy($policy);

        $policy = RunDependentJobPolicy::create();
        $policy->setTemplateId($statusTemplate->getTemplateId());
        $policy->addRunOnStatus(JobStatus::FAILED);
        $policy->addRunOnStatus(JobStatus::CANCELED);
        $policy->addRunOnStatus(JobStatus::TERMINATED);
        $extractTemplate->addRunDependentJobPolicy($policy);

        $this->sendCreateJob(CreateJob::createFor($extractTemplate));

        $extractPropertyTemplate = JobTemplate::create();
        $extractPropertyTemplate->setName('import_extract_property');
        $extractPropertyTemplate->setTemplateId(Uuid::generate());
        $extractPropertyTemplate->setRunner(QueueRunner::createFor('import_extract_property'));

        $policy = SubJobPolicy::create();
        $policy->setParentId($extractTemplate->getTemplateId());
        $extractPropertyTemplate->setSubJobPolicy($policy);

        $policy = GracePeriodPolicy::create();
        $policy->setPeriod(300);
        $extractPropertyTemplate->setGracePeriodPolicy($policy);

        $this->sendCreateJob(CreateJob::createFor($extractPropertyTemplate));

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

    private function sendCreateJob(CreateJob $createJob)
    {
        $queue = $this->context->createQueue('comrade_create_job');
        $message = $this->context->createMessage(JSON::encode($createJob));

        $this->context->createProducer()->send($queue, $message);
    }

    private function waitForService($dsn)
    {
        $fp = null;
        $limit = time() + 20;
        $host = parse_url($dsn, PHP_URL_HOST);
        $port = parse_url($dsn, PHP_URL_PORT);

        try {
            do {
                usleep(100000);

                $fp = @fsockopen($host, $port);
            } while (false == is_resource($fp) || $limit < time());

            if (false == $fp) {
                throw new \LogicException(sprintf('Failed to connect to "%s:%s"', $host, $port));
            }
        } finally {
            if (is_resource($fp)) {
                fclose($fp);
            }
        }
    }
}

register_cast_hooks();
register_object_hooks();

/** @var AmqpContext $queueContext */
$queueContext = dsn_to_context(getenv('ENQUEUE_DSN'));
$mongoClient = new Client(getenv('MONGO_DSN'));

$command = new \LoadDemoFixturesCommand($queueContext, $mongoClient);

$app = new Application('comrade-demo');
$app->add($command);
$app->setDefaultCommand($command->getName(), true);

$app->run();