<?php
namespace Comrade\Shared\Model;

use Comrade\Shared\ClassClosure;
use function Makasim\Values\add_object;
use function Makasim\Values\add_value;
use Makasim\Values\CastTrait;
use function Makasim\Values\get_object;
use function Makasim\Values\get_objects;
use function Makasim\Values\get_value;
use function Makasim\Values\set_object;
use function Makasim\Values\set_value;

class JobTemplate
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/JobTemplate.json';

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
     * @return mixed
     */
    public function getPayload()
    {
        return get_value($this, 'payload');
    }

    /**
     * @param mixed
     */
    public function setPayload($payload): void
    {
        set_value($this, 'payload', $payload);
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

    /**
     * @return GracePeriodPolicy|object|null
     */
    public function getGracePeriodPolicy(): ?GracePeriodPolicy
    {
        return get_object($this, 'gracePeriodPolicy', ClassClosure::create());
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
        return get_object($this, 'retryFailedPolicy', ClassClosure::create());
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
        return get_object($this, 'runSubJobsPolicy', ClassClosure::create());
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
        return get_object($this, 'subJobPolicy', ClassClosure::create());
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
        return get_object($this, 'exclusivePolicy', ClassClosure::create());
    }

    /**
     * @param ExclusivePolicy|null $exclusivePolicy
     */
    public function setExclusivePolicy(ExclusivePolicy $exclusivePolicy = null): void
    {
        set_object($this, 'exclusivePolicy', $exclusivePolicy);
    }

    /**
     * @param  RunDependentJobPolicy
     */
    public function addRunDependentJobPolicy(RunDependentJobPolicy $policy): void
    {
        add_object($this, 'runDependentJobPolicies', $policy);
    }

    /**
     * @return RunDependentJobPolicy[]
     */
    public function getRunDependentJobPolicies(): array
    {
        return iterator_to_array(get_objects($this, 'runDependentJobPolicies', ClassClosure::create()));
    }

    public function setRunner(Runner $executor): void
    {
        set_object($this, 'runner', $executor);
    }

    public function getRunner(): Runner
    {
        return get_object($this, 'runner', ClassClosure::create());
    }

    public function setCreatedAt(\DateTime $date): void
    {
        set_value($this, 'createdAt', $date);
    }

    public function getCreatedAt(): \DateTime
    {
        return get_value($this, 'createdAt', null, \DateTime::class);
    }

    public function setUpdatedAt(\DateTime $date): void
    {
        set_value($this, 'updatedAt', $date);
    }

    public function getUpdatedAt(): \DateTime
    {
        return get_value($this, 'updatedAt', null, \DateTime::class);
    }
}
