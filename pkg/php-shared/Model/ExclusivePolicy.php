<?php
namespace Comrade\Shared\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class ExclusivePolicy implements Policy
{
    use CreateTrait;

    const SCHEMA = 'http://comrade.forma-pro.com/schemas/policy/ExclusivePolicy.json';

    const MARK_JOB_AS_CANCELED = 'mark_job_as_canceled';

    const MARK_JOB_AS_FAILED = 'mark_job_as_failed';

    protected $values = [];

    /**
     * @param string $action
     */
    public function setOnDuplicateRun(string $action): void
    {
        set_value($this, 'onDuplicateRun', $action);
    }

    /**
     * @return string
     */
    public function getOnDuplicateRun(): string
    {
        return get_value($this, 'onDuplicateRun');
    }

    /**
     * @return bool
     */
    public function isMarkJobAsFailedOnDuplicateRun(): bool
    {
        return $this->getOnDuplicateRun() === static::MARK_JOB_AS_FAILED;
    }

    /**
     * @return bool
     */
    public function isMarkJobAsCanceledOnDuplicateRun(): bool
    {
        return $this->getOnDuplicateRun() === static::MARK_JOB_AS_CANCELED;
    }
}
