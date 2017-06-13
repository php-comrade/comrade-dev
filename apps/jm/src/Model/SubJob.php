<?php
namespace App\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class SubJob extends Job
{
    /**
     * @var array
     */
    private $values = [];

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