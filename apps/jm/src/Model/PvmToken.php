<?php
namespace App\Model;

use Comrade\Shared\ClassClosure;
use Comrade\Shared\Message\RunnerResult;
use Formapro\Pvm\Process;
use function Makasim\Values\get_object;
use function Makasim\Values\get_value;
use function Makasim\Values\set_object;

class PvmToken extends \Formapro\Pvm\Token
{
    /**
     * @return PvmProcess
     */
    public function getProcess(): Process
    {
        return parent::getProcess();
    }

    public function getJobId(): string
    {
        return $this->getProcess()->getJobId();
    }

    public function setRunnerResult(RunnerResult $runnerResult): void
    {
        set_object($this, 'runnerResult', $runnerResult);
    }

    public function hasRunnerResult(): bool
    {
        return (bool) get_value($this, 'runnerResult');
    }

    public function getRunnerResult(): RunnerResult
    {
        return get_object($this, 'runnerResult', ClassClosure::create());
    }
}
