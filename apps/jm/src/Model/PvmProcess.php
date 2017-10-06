<?php
namespace App\Model;

use Comrade\Shared\Model\Trigger;
use function Makasim\Values\get_object;
use function Makasim\Values\get_value;
use function Makasim\Values\set_object;
use function Makasim\Values\set_value;

class PvmProcess extends \Formapro\Pvm\Process
{
    public function getToken($id): PvmToken
    {
        return parent::getToken($id);
    }

    /**
     * @return PvmToken[]|\Traversable
     */
    public function getTokens(): \Traversable
    {
        return parent::getTokens();
    }

    public function setTrigger(Trigger $trigger): void
    {
        set_object($this, 'trigger', $trigger);
    }

    public function getTrigger(): Trigger
    {
        return get_object($this, 'trigger');
    }

    public function setJobTemplateId(string $templateId): void
    {
        set_value($this, 'jobTemplateId', $templateId);
    }

    public function getJobTemplateId(): string
    {
        return get_value($this, 'jobTemplateId');
    }

    public function setJobId(string $templateId): void
    {
        set_value($this, 'jobId', $templateId);
    }

    public function getJobId(): string
    {
        return get_value($this, 'jobId');
    }
}
