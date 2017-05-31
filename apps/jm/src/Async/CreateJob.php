<?php
namespace App\Async;

use App\Infra\Yadm\CreateTrait;
use App\Model\Job;
use function Makasim\Values\get_object;
use function Makasim\Values\get_values;
use function Makasim\Values\set_object;

class CreateJob implements \JsonSerializable
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/message/create-job.json';

    use CreateTrait;

    /**
     * @var array
     */
    private $values = [];

    /**
     * @return Job|object
     */
    public function getJob()
    {
        return get_object($this,'job');
    }

    /**
     * @param Job $job
     */
    public function setJob(Job $job)
    {
        set_object($this, 'job', $job);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }
}
