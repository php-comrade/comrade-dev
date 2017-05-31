<?php
namespace App\Infra\Yadm;

use App\Infra\JsonSchema\UnsupportedSchemaException;
use function Makasim\Values\register_global_hook;

class ObjectBuilderHook
{
    /**
     * @var string[]
     */
    private $classMap;

    /**
     * @param string[] $classMap
     */
    public function __construct(array $classMap)
    {
        $this->classMap = $classMap;
    }

    public function register()
    {
        register_global_hook('get_object_class', function(array $values) {
            if (isset($values['schema'])) {
                if (false == array_key_exists($values['schema'], $this->classMap)) {
                    throw new UnsupportedSchemaException(sprintf('An object has schema set "%s" but there is no class for it', $values['schema']));
                }

                return $this->classMap[$values['schema']];
            }
        });
    }
}
