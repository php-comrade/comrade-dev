<?php
namespace Comrade\Shared\Message;

use Comrade\Shared\Model\CreateTrait;
use Comrade\Shared\Model\Trigger;
use function Makasim\Values\get_object;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_object;
use function Makasim\Values\set_value;

class AddTrigger implements \JsonSerializable
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/message/AddTrigger.json';

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

    public function getTrigger(): Trigger
    {
        return get_object($this,'trigger');
    }

    public function setTrigger(Trigger $trigger): void
    {
        set_object($this,'trigger', $trigger);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }
}
