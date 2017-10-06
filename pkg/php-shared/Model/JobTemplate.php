<?php
namespace Comrade\Shared\Model;

use function Makasim\Values\add_object;
use Makasim\Values\CastTrait;
use function Makasim\Values\get_object;
use function Makasim\Values\get_objects;
use function Makasim\Values\get_value;
use function Makasim\Values\set_object;
use function Makasim\Values\set_value;

class JobTemplate
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/JobTemplate.json';

    use CreateTrait;
    use CastTrait;

    /**
     * @var array
     */
    protected $values = [];

    public function getTemplateId(): string
    {
        return get_value($this,'templateId');
    }

    public function setTemplateId(string $id): void
    {
        set_value($this, 'templateId', $id);
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return get_value($this, 'name');
    }

    /**
     * @param string $name
     */
    public function setName($name): void
    {
        set_value($this, 'name', $name);
    }

    /**
     * @return array
     */
    public function getDetails(): ?array
    {
        return get_value($this, 'details', []);
    }

    /**
     * @param array $details
     */
    public function setDetails($details): void
    {
        set_value($this, 'details', $details);
    }

    /**
     * @return GracePeriodPolicy|object|null
     */
    public function getGracePeriodPolicy(): ?GracePeriodPolicy
    {
        return get_object($this, 'gracePeriodPolicy');
    }

    /**
     * @param GracePeriodPolicy|null $gracePeriodPolicy
     */
    public function setGracePeriodPolicy(GracePeriodPolicy $gracePeriodPolicy = null): void
    {
        set_object($this, 'gracePeriodPolicy', $gracePeriodPolicy);
    }

    /**
     * @return RetryFailedPolicy|object|null
     */
    public function getRetryFailedPolicy(): ?RetryFailedPolicy
    {
        return get_object($this, 'retryFailedPolicy');
    }

    /**
     * @param RetryFailedPolicy|null $retryFailedPolicy
     */
    public function setRetryFailedPolicy(RetryFailedPolicy $retryFailedPolicy = null): void
    {
        set_object($this, 'retryFailedPolicy', $retryFailedPolicy);
    }

    /**
     * @return RunSubJobsPolicy|object|null
     */
    public function getRunSubJobsPolicy(): ?RunSubJobsPolicy
    {
        return get_object($this, 'runSubJobsPolicy');
    }

    /**
     * @param RunSubJobsPolicy|null $runSubJobsPolicy
     */
    public function setRunSubJobsPolicy(RunSubJobsPolicy $runSubJobsPolicy = null): void
    {
        set_object($this, 'runSubJobsPolicy', $runSubJobsPolicy);
    }

    public function getSubJobPolicy(): ?SubJobPolicy
    {
        return get_object($this, 'subJobPolicy');
    }

    public function setSubJobPolicy(SubJobPolicy $runSubJobsPolicy = null): void
    {
        set_object($this, 'subJobPolicy', $runSubJobsPolicy);
    }

    /**
     * @return ExclusivePolicy|object|null
     */
    public function getExclusivePolicy(): ?ExclusivePolicy
    {
        return get_object($this, 'exclusivePolicy');
    }

    /**
     * @param ExclusivePolicy|null $exclusivePolicy
     */
    public function setExclusivePolicy(ExclusivePolicy $exclusivePolicy = null): void
    {
        set_object($this, 'exclusivePolicy', $exclusivePolicy);
    }

    public function setRunner(Runner $executor): void
    {
        set_object($this, 'runner', $executor);
    }

    public function getRunner(): Runner
    {
        return get_object($this, 'runner');
    }

    public function setCreatedAt(\DateTime $date): void
    {
        set_value($this, 'createdAt', $date);
    }

    public function getCreatedAt(): \DateTime
    {
        return get_value($this, 'createdAt', null, \DateTime::class);
    }
}
