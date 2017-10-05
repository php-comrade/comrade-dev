<?php
namespace Comrade\Shared\Model;

use function Makasim\Values\add_object;
use function Makasim\Values\get_object;
use function Makasim\Values\get_objects;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_object;
use function Makasim\Values\set_value;

class Job extends JobTemplate
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/Job.json';

    /**
     * @var array
     */
    protected $values = [];

    public static function createFromTemplate(JobTemplate $jobTemplate) : Job
    {
        $values = get_values($jobTemplate);
        unset($values['schema']);

        return static::create($values);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return get_value($this,'id');
    }

    /**
     * @param string $id
     */
    public function setId(string $id)
    {
        set_value($this, 'id', $id);
    }

    /**
     * @return string
     */
    public function getProcessId(): string
    {
        return get_value($this,'processId');
    }

    /**
     * @param string $id
     */
    public function setProcessId(string $id)
    {
        set_value($this, 'processId', $id);
    }

    public function addResult(JobResult $jobResult):void
    {
        add_object($this, 'results', $jobResult);
    }

    /**
     * @return \Traversable|JobResult[]
     */
    public function getResults():\Traversable
    {
        return get_objects($this, 'results');
    }

    public function setCurrentResult(JobResult $jobResult):void
    {
        set_object($this, 'currentResult', $jobResult);
    }

    /**
     * @return JobResult|object
     */
    public function getCurrentResult():JobResult
    {
        return get_object($this, 'currentResult');
    }
}
