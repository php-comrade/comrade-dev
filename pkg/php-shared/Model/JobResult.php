<?php
namespace Comrade\Shared\Model;

use Comrade\Shared\ClassClosure;
use Makasim\Values\CastTrait;
use Makasim\Values\ValuesTrait;
use function Makasim\Values\get_object;
use function Makasim\Values\get_value;
use function Makasim\Values\set_object;
use function Makasim\Values\set_value;

class JobResult
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/JobResult.json';

    use CreateTrait;
    use CastTrait;
    use ValuesTrait {
        setValue as public;
        getValue as public;
    }

    public function getStatus(): string
    {
        return get_value($this, 'status');
    }

    public function setStatus(string $status) : void
    {
        set_value($this, 'status', $status);
    }

    /**
     * @param \DateTime $date
     */
    public function setCreatedAt(\DateTime $date): void
    {
        set_value($this, 'createdAt', $date);
    }

    /**
     * @return \DateTime|null
     */
    public function getCreatedAt(): \DateTime
    {
        return get_value($this, 'createdAt', null, \DateTime::class);
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
     * @param string $status
     * @param \DateTime|null $dateTime
     *
     * @return object|static
     */
    public static function createFor(string $status, \DateTime $dateTime = null)
    {
        $result = static::create();
        $result->setStatus($status);
        $result->setCreatedAt($dateTime ?: new \DateTime('now'));

        return $result;
    }
}
