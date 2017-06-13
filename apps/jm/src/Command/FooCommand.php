<?php
namespace App\Command;

use App\Async\Commands;
use App\Async\CreateJob;
use App\Infra\Uuid;
use App\Model\ExclusivePolicy;
use App\Model\GracePeriodPolicy;
use App\Model\JobTemplate;
use App\Model\RetryFailedPolicy;
use App\Model\RunSubJobsPolicy;
use App\Service\BuildMongoIndexesService;
use Enqueue\Client\ProducerV2Interface;
use function Makasim\Values\set_value;
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

        $jobTemplate = JobTemplate::create();
        $jobTemplate->setName('testJob');
        $jobTemplate->setTemplateId(Uuid::generate());
        $jobTemplate->setProcessTemplateId(Uuid::generate());
        $jobTemplate->setDetails(['foo' => 'fooVal', 'bar' => 'barVal']);

        $exclusivePolicy = ExclusivePolicy::create();
        $exclusivePolicy->setOnFailedSubJob(ExclusivePolicy::MARK_JOB_AS_FAILED);
        $jobTemplate->setExclusivePolicy($exclusivePolicy);

        $message = CreateJob::create();
        $message->setJobTemplate($jobTemplate);

        $this->getProducer()->sendCommand(Commands::CREATE_JOB, $message);

        $output->writeln('');
    }

    private function getProducer():ProducerV2Interface
    {
        return $this->container->get(ProducerV2Interface::class);
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