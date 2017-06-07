<?php
namespace App\Async;

use App\Infra\Yadm\CreateTrait;
use App\Model\Job;
use Formapro\Pvm\Token;
use function Makasim\Values\get_object;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_object;
use function Makasim\Values\set_value;

class DoJob implements \JsonSerializable
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/message/DoJob.json';

    use CreateTrait;

    /**
     * @var array
     */
    private $values = [];

    /**
     * @return Job|object
     */
    public function getJob():Job
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
     * @return string
     */
    public function getProcessId():string
    {
        return get_value($this,'processId');
    }

    /**
     * @param string $id
     */
    public function setProcessId(string $id):void
    {
        set_value($this, 'processId', $id);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }

    public static function createFor(Job $job, Token $token):DoJob
    {
        $message = static::create();
        $message->setJob($job);
        $message->setToken($token->getId());
        $message->setProcessId($token->getProcess()->getId());

        return $message;
    }
}
