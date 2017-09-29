<?php
namespace Comrade\Shared\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class ExclusivePolicy implements Policy
{
    use CreateTrait;

    const SCHEMA = 'http://jm.forma-pro.com/schemas/policy/ExclusivePolicy.json';

    const MARK_JOB_AS_CANCELED = 'mark_job_as_canceled';

    const MARK_JOB_AS_FAILED = 'mark_job_as_failed';

    private $values = [];

    /**
     * @param string $action
     */
    public function setOnFailedSubJob(string $action):void
    {
        set_value($this, 'onDuplicateRun', $action);
    }

    /**
     * @return string
     */
    public function getOnFailedSubJob(): string
    {
        return get_value($this, 'onDuplicateRun');
    }

    /**
     * @return bool
     */
    public function isMarkParentJobAsFailed():bool
    {
        return $this->getOnFailedSubJob() === static::MARK_JOB_AS_FAILED;
    }

    /**
     * @return bool
     */
    public function isMarkParentJobAsCanceled():bool
    {
        return $this->getOnFailedSubJob() === static::MARK_JOB_AS_CANCELED;
    }
}
