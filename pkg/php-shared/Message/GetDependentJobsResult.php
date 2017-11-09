<?php
namespace Comrade\Shared\Message;

use Comrade\Shared\ClassClosure;
use Comrade\Shared\Model\CreateTrait;
use Comrade\Shared\Model\Job;
use function Makasim\Values\add_object;
use function Makasim\Values\get_objects;
use function Makasim\Values\get_values;

class GetDependentJobsResult implements \JsonSerializable
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/message/GetDependentJobsResult.json';

    use CreateTrait;

    /**
     * @var array
     */
    protected $values = [];

    public function addJob(Job $job): void
    {
        add_object($this,'jobs', $job);
    }

    public function getJobs(): \Traversable
    {
        return get_objects($this,'jobs', ClassClosure::create());
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }
}
