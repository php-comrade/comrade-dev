<?php
namespace Comrade\Shared\Message;

use Comrade\Shared\ClassClosure;
use Comrade\Shared\Model\CreateTrait;
use Comrade\Shared\Model\Job;
use function Makasim\Values\get_object;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_object;
use function Makasim\Values\set_value;

class RunJob implements \JsonSerializable
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/message/RunJob.json';

    use CreateTrait;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @return Job|object
     */
    public function getJob(): Job
    {
        return get_object($this,'job', ClassClosure::create());
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
    public function getToken(): string
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
     * @deprecated can be taken from job
     *
     * @return string
     */
    public function getProcessId():string
    {
        return $this->getJob()->getProcessId();
    }

    public function setProcessId(string $id):void
    {
        throw new \LogicException('Should not be used');
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }

    public static function createFor(Job $job, string $token): RunJob
    {
        $message = static::create();
        $message->setJob($job);
        $message->setToken($token);

        return $message;
    }
}
