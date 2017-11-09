<?php
namespace Comrade\Shared\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class RunSubJobsPolicy implements Policy
{
    use CreateTrait;

    const SCHEMA = 'http://comrade.forma-pro.com/schemas/policy/RunSubJobsPolicy.json';

    const MARK_JOB_AS_FAILED = 'mark_job_as_failed';

    const MARK_JOB_AS_COMPLETED = 'mark_job_as_completed';

    protected $values = [];

    public function setOnFailedSubJob(string $action): void
    {
        set_value($this, 'onFailedSubJob', $action);
    }

    public function getOnFailedSubJob(): string
    {
        return get_value($this, 'onFailedSubJob');
    }

    public function setResultPayloadKey(string $key = null): void
    {
        set_value($this, 'resultPayloadKey', $key);
    }

    public function getResultPayloadKey(): ?string
    {
        return get_value($this, 'resultPayloadKey');
    }

    public function isMarkParentJobAsFailed(): bool
    {
        return $this->getOnFailedSubJob() === static::MARK_JOB_AS_FAILED;
    }

    public function isMarkParentJobAsCompleted(): bool
    {
        return $this->getOnFailedSubJob() === static::MARK_JOB_AS_COMPLETED;
    }
}
