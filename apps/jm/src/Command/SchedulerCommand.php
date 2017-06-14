<?php
namespace App\Command;

use Quartz\App\LoggerSubscriber;
use Quartz\Core\StdScheduler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class SchedulerCommand extends Command
{
    /**
     * @var StdScheduler
     */
    private $scheduler;

    /**
     * @param StdScheduler $scheduler
     */
    public function __construct(StdScheduler $scheduler)
    {
        parent::__construct('quartz:scheduler');

        $this->scheduler = $scheduler;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new LoggerSubscriber(new ConsoleLogger($output));

        $this->scheduler->getEventDispatcher()->addSubscriber($logger);
        $this->scheduler->start();
    }
}
