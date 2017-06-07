<?php
namespace App\Model;

use App\Infra\Yadm\CreateTrait;
use function Makasim\Values\add_object;
use function Makasim\Values\get_objects;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class JobTemplate
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/JobTemplate.json';

    use CreateTrait;

    /**
     * @var array
     */
    private $values = [];

    /**
     * @return string
     */
    public function getTemplateId(): string
    {
        return get_value($this,'templateId');
    }

    /**
     * @param string $id
     */
    public function setTemplateId(string $id)
    {
        set_value($this, 'templateId', $id);
    }

    /**
     * @return string
     */
    public function getProcessTemplateId(): string
    {
        return get_value($this,'processTemplateId');
    }

    /**
     * @param string $id
     */
    public function setProcessTemplateId(string $id)
    {
        set_value($this, 'processTemplateId', $id);
    }

    /**
     * @return string
     */
    public function getName():?string
    {
        return get_value($this, 'name');
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        set_value($this, 'name', $name);
    }

    /**
     * @param array $details
     */
    public function setDetails(array $details)
    {
        set_value($this, 'details', $details);
    }

    /**
     * @return array
     */
    public function getDetails(): ?array
    {
        return get_value($this, 'details', []);
    }

    /**
     * @param Policy $policy
     */
    public function addPolicy(Policy $policy)
    {
        add_object($this, 'polices', $policy);
    }

    /**
     * @return \Traversable|Policy[]
     */
    public function getPolices(): \Traversable
    {
        return get_objects($this,'polices');
    }
}
