<?php
namespace App\Async;

use App\Infra\Yadm\CreateTrait;
use App\Model\JobPattern;
use function Makasim\Values\get_object;
use function Makasim\Values\get_values;
use function Makasim\Values\set_object;

class CreateJob implements \JsonSerializable
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/message/createJob.json';

    use CreateTrait;

    /**
     * @var array
     */
    private $values = [];

    /**
     * @return JobPattern|object
     */
    public function getJobPattern() : ?JobPattern
    {
        return get_object($this,'jobPattern');
    }

    /**
     * @param JobPattern $jobPattern
     */
    public function setJobPattern(JobPattern $jobPattern)
    {
        set_object($this, 'jobPattern', $jobPattern);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }
}
