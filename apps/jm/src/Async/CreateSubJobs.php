<?php
namespace App\Async;

use App\Infra\Yadm\CreateTrait;
use App\Model\JobPattern;
use function Makasim\Values\add_object;
use function Makasim\Values\get_objects;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_object;
use function Makasim\Values\set_value;

class CreateSubJobs implements \JsonSerializable
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/message/CreateSubJobs.json';

    use CreateTrait;

    /**
     * @var array
     */
    private $values = [];

    /**
     * @return string
     */
    public function getParentProcessUid(): ?string
    {
        return get_value($this,'parentProcessUid');
    }

    /**
     * @param string $uid
     */
    public function setParentProcessUid($uid)
    {
        set_value($this, 'parentProcessUid', $uid);
    }

    /**
     * @return string
     */
    public function getParentJobUid(): ?string
    {
        return get_value($this,'parentJobUid');
    }

    /**
     * @param string $uid
     */
    public function setParentJobUid($uid)
    {
        set_value($this, 'parentJobUid', $uid);
    }

    /**
     * @return JobPattern[]|\Traversable
     */
    public function getSubJobPatterns() : \Traversable
    {
        return get_objects($this,'jobPatterns');
    }

    /**
     * @param JobPattern $jobPattern
     */
    public function addSubJobPatterns(JobPattern $jobPattern)
    {
        add_object($this, 'jobPatterns', $jobPattern);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }
}
