<?php

namespace Comrade\Shared\Model;

use function Makasim\Values\get_object;
use function Makasim\Values\get_value;

class Throwable
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/throwable.json';

    use CreateTrait;

    /**
     * @var array
     */
    protected $values = [];

    public function getRaw() :string
    {
        return get_value($this, 'raw');
    }

    public function getMessage() :string
    {
        return get_value($this, 'message');
    }

    public function getCode() :int
    {
        return get_value($this, 'code');
    }

    public function getFile() :string
    {
        return get_value($this, 'file');
    }

    public function getLine() :int
    {
        return get_value($this, 'line');
    }

    public function getTrace() :string
    {
        return get_value($this, 'trace');
    }

    public function getPrevious(): ?Throwable
    {
        return get_object($this, 'previous', ClassClosure::create());
    }

    public static function createFromThrowable(\Throwable $error)
    {
        return static::create(static::convertThrowable($error));
    }

    public static function convertThrowable(\Throwable $error):array
    {
        $rawError = [
            'schema' => static::SCHEMA,
            'raw' => (string) $error,
            'message' => $error->getMessage(),
            'code' => $error->getCode(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => $error->getTraceAsString(),
        ];

        if ($error->getPrevious()) {
            $rawError['previous'] = static::convertThrowable($error->getPrevious());
        }

        return $rawError;
    }
}
