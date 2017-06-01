<?php
namespace App\Model;

use App\Infra\Yadm\CreateTrait;
use function Makasim\Values\add_object;
use function Makasim\Values\get_objects;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class JobPattern
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/jobPattern.json';

    use CreateTrait;

    /**
     * @var array
     */
    private $values = [];

    /**
     * @return string
     */
    public function getUid(): ?string
    {
        return get_value($this,'uid');
    }

    /**
     * @param string $uid
     */
    public function setUid($uid)
    {
        set_value($this, 'uid', $uid);
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
