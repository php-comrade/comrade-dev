<?php
namespace App\Async;

use App\Model\JobTemplate;
use function Makasim\Values\add_object;
use function Makasim\Values\get_objects;
use function Makasim\Values\get_values;

class RunSubJobsResult extends JobResult
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/message/RunSubJobsResult.json';

    /**
     * @return JobTemplate[]|\Traversable
     */
    public function getJobTemplates():\Traversable
    {
        return get_objects($this,'jobTemplates');
    }

    /**
     * @param JobTemplate $jobTemplates
     */
    public function addJobTemplate(JobTemplate $jobTemplates):void
    {
        add_object($this, 'jobTemplates', $jobTemplates);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }
}
