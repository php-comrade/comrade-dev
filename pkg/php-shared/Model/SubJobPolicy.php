<?php
namespace Comrade\Shared\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class SubJobPolicy implements Policy
{
    use CreateTrait;

    const SCHEMA = 'http://comrade.forma-pro.com/schemas/policy/SubJobPolicy.json';

    protected $values = [];

    public function getParentId(): string
    {
        return get_value($this, 'parentId');
    }

    public function setParentId(string $parentId): void
    {
        set_value($this, 'parentId', $parentId);
    }
}
