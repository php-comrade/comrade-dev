<?php
namespace App\Model;

use App\Infra\Yadm\CreateTrait;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class RunSubJobsPolicy implements Policy
{
    use CreateTrait;

    const SCHEMA = 'http://jm.forma-pro.com/schemas/RunSubJobsPolicy.json';

    const MARK_PARENT_JOB_AS_FAILED = 'mark_parent_job_as_failed';

    const MARK_PARENT_JOB_AS_COMPLETED = 'mark_parent_job_as_completed';

    private $values = [];

    /**
     * @param string $action
     */
    public function setOnFailedSubJob(string $action):void
    {
        set_value($this, 'onFailedSubJob', $action);
    }

    /**
     * @return string
     */
    public function getOnFailedSubJob(): string
    {
        return get_value($this, 'onFailedSubJob');
    }
}
