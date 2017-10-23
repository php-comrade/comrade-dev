<?php
namespace App\Infra\Yadm;

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
            if (false == isset($values['schema'])) {
                return;
            }
            if (false == array_key_exists($values['schema'], $this->classMap)) {
                return;
            }

            return $this->classMap[$values['schema']];
        });
    }
}
