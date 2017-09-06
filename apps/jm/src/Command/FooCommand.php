<?php
namespace App\Command;

use App\Async\Commands;
use App\Async\CreateJob;
use App\Infra\Uuid;
use App\Model\ExclusivePolicy;
use App\Model\JobTemplate;
use App\Model\QueueRunner;
use App\Model\SimpleTrigger;
use App\Service\BuildMongoIndexesService;
use Enqueue\Client\ProducerInterface;
use function Makasim\Values\get_values;
use Makasim\Yadm\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class FooCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected function configure()
    {
        $this
            ->setName('foo')
            ->addOption('drop', null, InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('drop')) {
            foreach ($this->getYadmRegistry()->getStorages() as $name => $storage) {
                $storage->getCollection()->drop();
            }
        }

        $this->getBuildMongoIndexesService()->build();

        $jobTemplate = $this->createDemoSuccessJob();
//        $jobTemplate = $this->createFooJob();

        $message = CreateJob::create();
        $message->setJobTemplate($jobTemplate);

        $output->writeln(json_encode(get_values($jobTemplate), JSON_PRETTY_PRINT));
        $this->getProducer()->sendCommand(Commands::CREATE_JOB, $message);

        $output->writeln('');
    }

    protected function createDemoSuccessJob():JobTemplate
    {
        $template = JobTemplate::create();
        $template->setName('demo_success_job');
        $template->setTemplateId(Uuid::generate());
        $template->setProcessTemplateId(Uuid::generate());
        $template->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);

        $runner = QueueRunner::create();
        $runner->setQueue('demo_success_job');
        $template->setRunner($runner);

        $simpleTrigger = SimpleTrigger::create();
        $simpleTrigger->setIntervalInSeconds(0);
        $simpleTrigger->setRepeatCount(0);
        $simpleTrigger->setMisfireInstruction(SimpleTrigger::MISFIRE_INSTRUCTION_FIRE_NOW);
        $template->addTrigger($simpleTrigger);

        return $template;
    }

    private function createFooJob():JobTemplate
    {
        $jobTemplate = JobTemplate::create();
        $jobTemplate->setName('testJob');
        $jobTemplate->setTemplateId(Uuid::generate());
        $jobTemplate->setProcessTemplateId(Uuid::generate());
        $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);

        $runner = QueueRunner::create();
        $runner->setQueue('demo_job');
        $jobTemplate->setRunner($runner);

        $simpleTrigger = SimpleTrigger::create();
        $simpleTrigger->setIntervalInSeconds(0);
        $simpleTrigger->setRepeatCount(0);
        $simpleTrigger->setMisfireInstruction(SimpleTrigger::MISFIRE_INSTRUCTION_FIRE_NOW);
        $jobTemplate->addTrigger($simpleTrigger);

//        $cronTrigger = CronTrigger::create();
//        $cronTrigger->setExpression('*/20 * * * * *');
//        $cronTrigger->setMisfireInstruction(CronTrigger::MISFIRE_INSTRUCTION_FIRE_ONCE_NOW);
//        $jobTemplate->addTrigger($cronTrigger);
//
        $exclusivePolicy = ExclusivePolicy::create();
        $exclusivePolicy->setOnFailedSubJob(ExclusivePolicy::MARK_JOB_AS_FAILED);
        $jobTemplate->setExclusivePolicy($exclusivePolicy);

//        $gracePeriodPolicy = GracePeriodPolicy::create();
//        $gracePeriodPolicy->setPeriod(30);
//        $jobTemplate->setGracePeriodPolicy($gracePeriodPolicy);

        return $jobTemplate;
    }

    private function getProducer():ProducerInterface
    {
        return $this->container->get(ProducerInterface::class);
    }
    private function getYadmRegistry():Registry
    {
        return $this->container->get('yadm');
    }

    private function getBuildMongoIndexesService():BuildMongoIndexesService
    {
        return $this->container->get(BuildMongoIndexesService::class);
    }
}