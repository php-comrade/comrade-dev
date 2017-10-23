<?php
namespace Comrade\Shared;

final class ClassClosure
{
    /**
     * @var ClassClosure
     */
    private static $instance;

    /**
     * @var ComradeClassMap
     */
    private $classMap;

    private function __construct(ComradeClassMap $classMap)
    {
        $this->classMap = $classMap;
    }

    public function __invoke(array $values): ?string
    {
        $classMap = $this->classMap->get();
        if (array_key_exists('schema', $values) && array_key_exists($values['schema'], $classMap)) {
            return $classMap[$values['schema']];
        }
    }

    public static function create(): ClassClosure
    {
        if (false == self::$instance) {
            self::$instance = new ClassClosure(new ComradeClassMap());
        }

        return self::$instance;
    }
}