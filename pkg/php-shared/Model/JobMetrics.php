<?php

namespace Comrade\Shared\Model;

use Makasim\Values\CastTrait;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;
use Makasim\Values\ValuesTrait;

class JobMetrics
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/JobMetrics.json';

    use CreateTrait;
    use CastTrait;
    use ValuesTrait {
        setValue as public;
        getValue as public;
    }

    public function setTemplateId(string $id): void
    {
        set_value($this, 'templateId', $id);
    }

    public function getTemplateId(): string
    {
        return get_value($this, 'templateId');
    }

    public function setJobId(string $id): void
    {
        set_value($this, 'jobId', $id);
    }

    public function getJobId(): string
    {
        return get_value($this, 'jobId');
    }

    public function getStatus(): string
    {
        return get_value($this, 'status');
    }

    public function setStatus(string $status):  void
    {
        set_value($this, 'status', $status);
    }

    public function setScheduledTime(\DateTime $time)
    {
        set_value($this, 'scheduledTime', $time);
    }

    public function getScheduledTime(): \DateTime
    {
        return get_value($this, 'scheduledTime', null, \DateTime::class);
    }

    public function setStartTime(\DateTime $time)
    {
        set_value($this, 'startTime', $time);
    }

    public function getStartTime(): \DateTime
    {
        return get_value($this, 'startTime', null, \DateTime::class);
    }

    public function setWaitTime(int $wait)
    {
        set_value($this, 'waitTime', $wait);
    }

    public function getWaitTime(): int
    {
        return get_value($this, 'waitTime');
    }

    public function setDuration(int $duration)
    {
        set_value($this, 'duration', $duration);
    }

    public function getDuration(): int
    {
        return get_value($this, 'duration');
    }

    public function setMemory(int $memory)
    {
        set_value($this, 'memory', $memory);
    }

    public function getMemory(): int
    {
        return get_value($this, 'memory');
    }
}
