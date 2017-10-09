<?php
namespace App\Infra\Error;

use Makasim\Values\ValuesTrait;

class Error implements \JsonSerializable
{
    use ValuesTrait {
        setValue as public;
        getValue as public;
        addValue as public;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->values;
    }
}
