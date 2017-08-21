<?php
namespace App\Async;

use App\Infra\Yadm\CreateTrait;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_value;

class GetTimeline implements \JsonSerializable
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/message/GetTimeline.json';

    use CreateTrait;

    /**
     * @var array
     */
    private $values = [];

    /**
     * @return string
     */
    public function getJobTemplateId(): string
    {
        return get_value($this,'jobTemplateId');
    }

    /**
     * @param string $id
     */
    public function setJobTemplateId(string $id):void
    {
        set_value($this, 'jobTemplateId', $id);
    }

    /**
     * @return int
     */
    public function getLimit():int
    {
        return get_value($this,'limit', 20);
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit):void
    {
        set_value($this, 'limit', $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }
}