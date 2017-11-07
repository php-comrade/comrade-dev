<?php
namespace Comrade\Shared\Message;

use Comrade\Shared\ClassClosure;
use Comrade\Shared\Message\Part\SubJob;
use Comrade\Shared\Model\CreateTrait;
use Comrade\Shared\Model\JobResultMetrics;
use Comrade\Shared\Model\Throwable;
use function Makasim\Values\add_object;
use function Makasim\Values\get_object;
use function Makasim\Values\get_objects;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_object;
use function Makasim\Values\set_value;

class RunnerResult implements \JsonSerializable
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/message/RunnerResult.json';

    use CreateTrait;

    /**
     * @var array
     */
    protected $values = [];

    public function getJobId(): string
    {
        return get_value($this,'jobId');
    }

    public function setJobId(string $id): void
    {
        set_value($this, 'jobId', $id);
    }

    public function getToken(): string
    {
        return get_value($this,'token');
    }

    public function setToken(string $token): void
    {
        set_value($this, 'token', $token);
    }

    public function getAction(): string
    {
        return get_value($this,'action');
    }

    public function setAction(string $action): void
    {
        set_value($this, 'action', $action);
    }

    public function setTimestamp(int $time): void
    {
        set_value($this, 'timestamp', $time);
    }

    public function getTimestamp(): int
    {
        return get_value($this,'timestamp');
    }

    /**
     * @return mixed
     */
    public function getResultPayload()
    {
        return get_value($this, 'resultPayload');
    }

    /**
     * @param mixed
     */
    public function setResultPayload($payload): void
    {
        set_value($this, 'resultPayload', $payload);
    }

    public function setError(Throwable $error)
    {
        set_object($this, 'error', $error);
    }

    public function getError(): ?Throwable
    {
        return get_object($this, 'error', ClassClosure::create());
    }

    public function setMetrics(JobResultMetrics $metrics): void
    {
        set_object($this, 'metrics', $metrics);
    }

    public function getMetrics(): ?JobResultMetrics
    {
        return get_object($this, 'metrics', ClassClosure::create());
    }

    /**
     * @return SubJob[]|\Traversable
     */
    public function getSubJobs(): \Traversable
    {
        return get_objects($this,'subJobs', ClassClosure::create());
    }

    public function addSubJob(SubJob $subJob): void
    {
        add_object($this, 'subJobs', $subJob);
    }

    public function jsonSerialize()
    {
        return get_values($this);
    }

    public static function createFor(RunJob $runJob, string $action): RunnerResult
    {
        $message = static::create();
        $message->setJobId($runJob->getJob()->getId());
        $message->setToken($runJob->getToken());
        $message->setTimestamp(time());
        $message->setAction($action);

        return $message;
    }
}
