<?php

namespace App;

class ProcessMetrics
{
    /**
     * @var int
     */
    private $startTime;

    /**
     * @var int
     */
    private $startMem;

    /**
     * @var int
     */
    private $stopTime;

    /**
     * @var int
     */
    private $stopMem;

    /**
     * @var int
     */
    private $duration;

    /**
     * @var int
     */
    private $memory;

    /**
     * @var int
     */
    private $finished;

    private function __construct()
    {
        $this->startTime = (int) microtime(true) * 1000;
        $this->startMem = memory_get_usage();
        $this->finished = false;
    }

    public static function start() :ProcessMetrics
    {
        return new static();
    }

    public function stop() :void
    {
        $this->stopTime = (int) microtime(true) * 1000;
        $this->stopMem = memory_get_usage();

        $this->duration = $this->stopTime - $this->startTime;
        $this->memory = $this->stopMem - $this->startMem;

        $this->finished = true;
    }

    public function getStartTime() :int
    {
        return $this->startTime;
    }

    public function getStopTime() :int
    {
        return $this->stopTime;
    }

    /**
     * @return int
     */
    public function getDuration() :int
    {
        if (false == $this->finished) {
            throw new \LogicException('Is not finished yet');
        }

        return $this->duration;
    }

    /**
     * @return int
     */
    public function getMemory() :int
    {
        if (false == $this->finished) {
            throw new \LogicException('Is not finished yet');
        }

        return $this->memory;
    }
}
