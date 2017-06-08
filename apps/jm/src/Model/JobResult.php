<?php
namespace App\Model;

use App\Infra\Yadm\CreateTrait;
use Makasim\Values\CastTrait;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;
use Makasim\Values\ValuesTrait;

class JobResult
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/JobResult.json';

    use CreateTrait;
    use CastTrait;
    use ValuesTrait {
        setValue as public;
        getValue as public;
    }

    public function getStatus() :string
    {
        return get_value($this, 'status');
    }

    public function setStatus(string $status) : void
    {
        set_value($this, 'status', $status);
    }

    public function isNew():bool
    {
        return $this->getStatus() === Job::STATUS_NEW;
    }

    public function isRunning():bool
    {
        return $this->getStatus() === Job::STATUS_RUNNING;
    }

    public function isCanceled():bool
    {
        return $this->getStatus() === Job::STATUS_CANCELED;
    }

    public function isCompleted():bool
    {
        return $this->getStatus() === Job::STATUS_COMPLETED;
    }

    public function isFailed():bool
    {
        return $this->getStatus() === Job::STATUS_FAILED;
    }

    public function isRunSubJobs():bool
    {
        return $this->getStatus() === Job::STATUS_RUN_SUB_JOBS;
    }

    public function isRunningSubJobs():bool
    {
        return $this->getStatus() === Job::STATUS_RUNNING_SUB_JOBS;
    }

    public function isTerminated():bool
    {
        return $this->getStatus() === Job::STATUS_TERMINATED;
    }

    /**
     * @param \DateTime $date
     */
    public function setCreatedAt(\DateTime $date):void
    {
        set_value($this, 'createdAt', $date);
    }

    /**
     * @return \DateTime|null
     */
    public function getCreatedAt(): ?\DateTime
    {
        return get_value($this, 'createdAt', null, \DateTime::class);
    }

    /**
     * @param string $status
     * @param \DateTime|null $dateTime
     *
     * @return object|static
     */
    public static function createFor(string $status, \DateTime $dateTime = null)
    {
        $result = static::create();
        $result->setStatus($status);
        $result->setCreatedAt($dateTime ?: new \DateTime('now'));

        return $result;
    }
}
