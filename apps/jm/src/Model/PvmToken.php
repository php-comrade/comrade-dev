<?php
namespace App\Model;

use Comrade\Shared\Message\RunnerResult;
use function Makasim\Values\get_object;
use function Makasim\Values\set_object;

/**
 * @method PvmProcess getProcess()
 */
class PvmToken extends \Formapro\Pvm\Token
{
    public function getJobId(): string
    {
        return $this->getProcess()->getJobId();
    }

    public function setRunnerResult(RunnerResult $runnerResult): void
    {
        set_object($this, 'runnerResult', $runnerResult);
    }

    public function getRunnerResult(): RunnerResult
    {
        return get_object($this, 'runnerResult');
    }
}
