<?php
namespace Comrade\Shared\Message;

use Comrade\Shared\ClassClosure;
use Comrade\Shared\Model\CreateTrait;
use Comrade\Shared\Model\JobResult as JobResultModel;
use function Makasim\Values\get_object;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_object;
use function Makasim\Values\set_value;

class JobResult implements \JsonSerializable
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/message/JobResult.json';

    use CreateTrait;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @return string
     */
    public function getJobId():string
    {
        return get_value($this,'jobId');
    }

    /**
     * @param string $id
     */
    public function setJobId(string $id)
    {
        set_value($this, 'jobId', $id);
    }

    /**
     * @return JobResultModel|object
     */
    public function getResult():JobResultModel
    {
        return get_object($this,'jobResult', ClassClosure::create());
    }

    /**
     * @param JobResultModel $jobResult
     */
    public function setResult(JobResultModel $jobResult):void
    {
        set_object($this, 'jobResult', $jobResult);
    }

    /**
     * @return string
     */
    public function getToken():string
    {
        return get_value($this,'token');
    }

    /**
     * @param string $token
     */
    public function setToken(string $token)
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
