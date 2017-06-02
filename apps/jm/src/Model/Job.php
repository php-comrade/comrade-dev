<?php
namespace App\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_value;

class Job extends JobPattern
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/job.json';

    const STATUS_NEW = 'new';

    const STATUS_RUNNING = 'running';

    const STATUS_CANCELED = 'canceled';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_TERMINATED = 'terminated';

    /**
     * @var array
     */
    private $values = [];

    public static function createFromPattern(JobPattern $jobPattern) : Job
    {
        $values = get_values($jobPattern);
        unset($values['schema']);

        return static::create($values);
    }

    public function getStatus() :string
    {
        return get_value($this, 'status', static::STATUS_NEW);
    }

    public function setStatus(string $status) : void
    {
        set_value($this, 'status', $status);
    }

    public function isNew():bool
    {
        return $this->getStatus() === static::STATUS_NEW;
    }

    public function isRunning():bool
    {
        return $this->getStatus() === static::STATUS_RUNNING;
    }

    public function isCanceled():bool
    {
        return $this->getStatus() === static::STATUS_CANCELED;
    }

    public function isCompleted():bool
    {
        return $this->getStatus() === static::STATUS_COMPLETED;
    }

    public function isFailed():bool
    {
        return $this->getStatus() === static::STATUS_FAILED;
    }

    public function isTerminated():bool
    {
        return $this->getStatus() === static::STATUS_TERMINATED;
    }
}
