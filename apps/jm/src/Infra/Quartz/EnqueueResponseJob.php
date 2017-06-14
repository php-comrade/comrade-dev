<?php
namespace App\Infra\Quartz;

use Enqueue\Client\ProducerV2Interface;
use Quartz\Core\Job;
use Quartz\Core\JobExecutionContext;

class EnqueueResponseJob implements Job
{
    /**
     * @var int msec
     */
    private $timeout;
    /**
     * @var ProducerV2Interface
     */
    private $producer;

    /**
     * @param ProducerV2Interface $producer
     */
    public function __construct(ProducerV2Interface $producer)
    {
        $this->timeout = 5000;
        $this->producer = $producer;
    }

    /**
     * @param int $timeout msec
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int) $timeout;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(JobExecutionContext $context)
    {
        $data = $context->getMergedJobDataMap();

        if (false == empty($data['command'])) {
            $context->getTrigger()->setErrorMessage('There is no enqueue topic');

            $context->setUnscheduleFiringTrigger();

            return;
        }

        $command = $data['command'];
        unset($data['command']);

        $this->producer->sendCommand($command, $data);
    }
}
