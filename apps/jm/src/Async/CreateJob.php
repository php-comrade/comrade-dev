<?php
namespace App\Async;

use App\Infra\Yadm\CreateTrait;
use App\Model\JobTemplate;
use function Makasim\Values\get_object;
use function Makasim\Values\get_values;
use function Makasim\Values\set_object;

class CreateJob implements \JsonSerializable
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/message/CreateJob.json';

    use CreateTrait;

    /**
     * @var array
     */
    private $values = [];

    /**
     * @return JobTemplate|object
     */
    public function getJobTemplate() : ?JobTemplate
    {
        return get_object($this,'jobTemplate');
    }

    /**
     * @param JobTemplate $jobTemplate
     */
    public function setJobTemplate(JobTemplate $jobTemplate)
    {
        set_object($this, 'jobTemplate', $jobTemplate);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }
}
