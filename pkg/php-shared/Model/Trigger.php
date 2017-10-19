<?php
namespace Comrade\Shared\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

abstract class Trigger
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/trigger/Trigger.json';

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
}
