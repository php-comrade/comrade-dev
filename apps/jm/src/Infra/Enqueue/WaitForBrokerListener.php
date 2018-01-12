<?php
namespace App\Infra\Enqueue;

use Enqueue\Symfony\Client\ConsumeMessagesCommand as ClientConsumeMessagesCommand;
use Enqueue\Symfony\Consumption\ConsumeMessagesCommand as ConsumptionConsumeMessagesCommand;
use Psr\Log\LoggerInterface;
use Quartz\Bundle\Command\SchedulerCommand;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WaitForBrokerListener implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $brokerDsn;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(string $brokerDsn, LoggerInterface $logger)
    {
        $this->brokerDsn = $brokerDsn;
        $this->logger = $logger;
    }

    public function beforeCommand(ConsoleCommandEvent $event)
    {
        if (false == (
            $event->getCommand() instanceof ClientConsumeMessagesCommand ||
            $event->getCommand() instanceof ConsumptionConsumeMessagesCommand ||
            $event->getCommand() instanceof SchedulerCommand
        )) {
            return;
        }

        $fp = null;
        $limit = time() + 20;
        $host = parse_url($this->brokerDsn, PHP_URL_HOST);
        $port = parse_url($this->brokerDsn, PHP_URL_PORT);

        try {
            do {
                $fp = @fsockopen($host, $port);

                if (false == is_resource($fp)) {
                    $this->logger->debug(sprintf('service is not running %s:%s', $host, $port));
                    sleep(1);
                }
            } while (false == is_resource($fp) || $limit < time());

            if (false == $fp) {
                throw new \LogicException(sprintf('Failed to connect to "%s:%s"', $host, $port));
            }

            $this->logger->debug(sprintf('service is online %s:%s', $host, $port));
        } finally {
            if (is_resource($fp)) {
                fclose($fp);
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => 'beforeCommand'
        ];
    }
}