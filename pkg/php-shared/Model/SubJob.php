<?php
namespace Comrade\Shared\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class SubJob extends Job
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/SubJob.json';

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @param string $id
     */
    public function setParentId(string $id):void
    {
        set_value($this, 'parentId', $id);
    }

    /**
     * @return string
     */
    public function getParentId():string
    {
        return get_value($this, 'parentId');
    }
}
