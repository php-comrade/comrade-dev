<?php
namespace Comrade\Shared\Model;

use function Makasim\Values\add_value;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class RunDependentJobPolicy implements Policy
{
    use CreateTrait;

    const SCHEMA = 'http://comrade.forma-pro.com/schemas/policy/RunDependentJobPolicy.json';

    protected $values = [];

    public function setRunAlways(bool $bool): void
    {
        set_value($this, 'runAlways', $bool);
    }

    public function isRunAlways(): bool
    {
        return get_value($this,'runAlways', false);
    }

    public function addRunOnStatus(string $status): void
    {
        add_value($this, 'runOnStatus', $status);
    }

    /**
     * @return string[]
     */
    public function getRunOnStatus(): array
    {
        return get_value($this,'runOnStatus', []);
    }

    public function getTemplateId(): string
    {
        return get_value($this, 'templateId');
    }

    public function setTemplateId(string $jobId): void
    {
        set_value($this, 'templateId', $jobId);
    }
}
