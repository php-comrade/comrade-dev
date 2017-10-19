<?php
namespace Comrade\Shared\Message;

use Comrade\Shared\Model\CreateTrait;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_value;

class GetTriggers implements \JsonSerializable
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/message/GetTriggers.json';

    use CreateTrait;

    /**
     * @var array
     */
    protected $values = [];

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
    public function setTemplateId(string $id):void
    {
        set_value($this, 'templateId', $id);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }
}
