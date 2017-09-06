<?php
namespace App\Model;

use App\Infra\Yadm\CreateTrait;
use App\JobStatus;
use Makasim\Values\CastTrait;
use function Makasim\Values\get_object;
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

    public function getStatus():int
    {
        return get_value($this, 'status');
    }

    public function setStatus(int $status) : void
    {
        set_value($this, 'status', $status);
    }

    public function isNew():bool
    {
        return JobStatus::isNew($this);
    }

    public function isRunning():bool
    {
        return JobStatus::isRunning($this);
    }

    public function isCanceled():bool
    {
        return JobStatus::isCanceled($this);
    }

    public function isCompleted():bool
    {
        return JobStatus::isCompleted($this);
    }

    public function isDone():bool
    {
        return JobStatus::isDone($this);
    }

    public function isFailed():bool
    {
        return JobStatus::isFailed($this);
    }

    public function isRunSubJobs():bool
    {
        return JobStatus::isRunSubJobs($this);
    }

    public function isRunningSubJobs():bool
    {
        return JobStatus::isRunningSubJobs($this);
    }

    public function isTerminated():bool
    {
        return JobStatus::isTerminated($this);
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

    public function setStartTime(int $time)
    {
        set_value($this, 'startTime', $time);
    }

    public function getStartTime() :?int
    {
        return get_value($this, 'startTime');
    }

    public function setStopTime(int $time)
    {
        set_value($this, 'stopTime', $time);
    }

    public function getStopTime() :?int
    {
        return get_value($this, 'stopTime');
    }

    public function setDuration(int $duration)
    {
        set_value($this, 'duration', $duration);
    }

    public function getDuration() :?int
    {
        return get_value($this, 'duration');
    }

    public function setMemory(int $memory)
    {
        set_value($this, 'memory', $memory);
    }

    public function getMemory() :?int
    {
        return get_value($this, 'memory');
    }

    public function setError(string $error)
    {
        set_value($this, 'error', $error);
    }

    public function getError() :?Throwable
    {
        return get_object($this, 'error', Throwable::class);
    }

    /**
     * @param int $status
     * @param \DateTime|null $dateTime
     *
     * @return object|static
     */
    public static function createFor(int $status, \DateTime $dateTime = null)
    {
        $result = static::create();
        $result->setStatus($status);
        $result->setCreatedAt($dateTime ?: new \DateTime('now'));

        return $result;
    }
}
