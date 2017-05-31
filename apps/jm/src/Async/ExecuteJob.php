<?php
namespace App\Async;

use App\Infra\Yadm\CreateTrait;
use App\Model\Job;
use function Makasim\Values\get_object;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_object;
use function Makasim\Values\set_value;

class ExecuteJob implements \JsonSerializable
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/message/execute-job.json';

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
     * @return string
     */
    public function getToken()
    {
        return get_value($this,'token');
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        set_value($this, 'token', $token);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }
}
