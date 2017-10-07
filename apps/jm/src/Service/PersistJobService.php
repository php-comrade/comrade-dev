<?php
namespace App\Service;

use App\Model\Job;
use App\Storage\JobStorage;
use App\Topics;
use Enqueue\Client\ProducerInterface;
use function Makasim\Values\get_values;
use Makasim\Yadm\ChangesCollector;
use function Makasim\Yadm\get_object_id;

class PersistJobService
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var ChangesCollector
     */
    private $changesCollector;

    public function __construct(JobStorage $jobStorage, ProducerInterface $producer, ChangesCollector $changesCollector)
    {
        $this->jobStorage = $jobStorage;
        $this->producer = $producer;
        $this->changesCollector = $changesCollector;
    }

    public function persist(Job $job): void
    {
        if (empty($this->changesCollector->changes($job))) {
            return;
        }

        if (false == $job->getUpdatedAt()) {
            $job->setUpdatedAt(new \DateTime('now'));
        }

        if (get_object_id($job, true)) {
            $this->jobStorage->update($job);
        } else {
            if (false == $job->getCreatedAt()) {
                $job->setCreatedAt(new \DateTime('now'));
            }

            $this->jobStorage->insert($job);
        }

        $refreshedJob = $this->jobStorage->getOneById($job->getId());
        $this->producer->sendEvent(Topics::JOB_UPDATED, get_values($refreshedJob));
    }
}
