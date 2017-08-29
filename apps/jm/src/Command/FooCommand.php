<?php
namespace App\Command;

use App\Async\Commands;
use App\Async\CreateJob;
use App\Infra\Uuid;
use App\Model\ExclusivePolicy;
use App\Model\JobTemplate;
use App\Service\BuildMongoIndexesService;
use App\Ws\Ratchet\AmqpPusher;
use Enqueue\Client\ProducerInterface;
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
        /** @var AmqpPusher $pusher */
        $pusher = $this->container->get('gos_web_socket.amqp.pusher');
        $pusher->push(['key' => 'value'], 'events');


        return;


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
        set_value($jobTemplate, 'enqueue.queue', 'demo_job');

//        $simpleTrigger = SimpleTrigger::create();
//        $simpleTrigger->setIntervalInSeconds(30);
//        $simpleTrigger->setRepeatCount(3);
//        $simpleTrigger->setMisfireInstruction(SimpleTrigger::MISFIRE_INSTRUCTION_FIRE_NOW);
//        $jobTemplate->addTrigger($simpleTrigger);
//
//        $cronTrigger = CronTrigger::create();
//        $cronTrigger->setExpression('*/20 * * * * *');
//        $cronTrigger->setMisfireInstruction(CronTrigger::MISFIRE_INSTRUCTION_FIRE_ONCE_NOW);
//        $jobTemplate->addTrigger($cronTrigger);
//
        $exclusivePolicy = ExclusivePolicy::create();
        $exclusivePolicy->setOnFailedSubJob(ExclusivePolicy::MARK_JOB_AS_FAILED);
        $jobTemplate->setExclusivePolicy($exclusivePolicy);

//        $gracePeriodPolicy = GracePeriodPolicy::create();
//        $gracePeriodPolicy->setPeriodEndsAt(new \DateTime('now + 30 seconds'));
//        $jobTemplate->setGracePeriodPolicy($gracePeriodPolicy);

        $message = CreateJob::create();
        $message->setJobTemplate($jobTemplate);

//        $output->writeln(json_encode(get_values($jobTemplate), JSON_PRETTY_PRINT));
        $this->getProducer()->sendCommand(Commands::CREATE_JOB, $message);

        $output->writeln('');
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