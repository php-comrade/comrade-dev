<?php
namespace App\Infra\Yadm;

use Makasim\Yadm\Hydrator as YadmHydrator;

class SchemaBasedHydrator extends YadmHydrator
{
    public function __construct($modelClass)
    {
        // the build_object hook provides a correct class based on schema.
        parent::__construct(null);
    }
}
