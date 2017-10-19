<?php
namespace Comrade\Shared\Message;

use Comrade\Shared\Model\CreateTrait;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_value;

class GetJob implements \JsonSerializable
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/message/GetJob.json';

    use CreateTrait;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @return string
     */
    public function getJobId(): string
    {
        return get_value($this,'jobId');
    }

    /**
     * @param string $id
     */
    public function setJobTemplateId(string $id):void
    {
        set_value($this, 'jobId', $id);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }
}
