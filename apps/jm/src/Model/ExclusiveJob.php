<?php
namespace App\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class ExclusiveJob
{
    /**
     * @var array
     */
    private $values = [];

    /**
     * @param string $name
     */
    public function setName(string $name):void
    {
        set_value($this,'name', $name);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return get_value($this,'name');
    }
}
