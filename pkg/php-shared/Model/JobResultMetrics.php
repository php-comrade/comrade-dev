<?php
namespace Comrade\Shared\Model;

use function Makasim\Values\add_value;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class JobResultMetrics
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/JobResultMetrics.json';

    use CreateTrait;

    protected $values = [];

    public function setStartTime(int $time)
    {
        set_value($this, 'startTime', $time);
    }

    public function getStartTime(): int
    {
        return get_value($this, 'startTime');
    }

    public function setStopTime(int $time)
    {
        set_value($this, 'stopTime', $time);
    }

    public function getStopTime(): int
    {
        return get_value($this, 'stopTime');
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

    public function addLog(string $log)
    {
        add_value($this, 'logs', $log);
    }

    public function getLogs(): array
    {
        return get_value($this, 'logs', []);
    }
}
