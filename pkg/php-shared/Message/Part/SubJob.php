<?php
namespace Comrade\Shared\Message\Part;

use Comrade\Shared\Model\CreateTrait;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class SubJob
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/message/part/SubJob.json';

    use CreateTrait;

    /**
     * @var array
     */
    protected $values = [];

    public function getName(): string
    {
        return get_value($this, 'name');
    }

    public function setName(string $name): void
    {
        set_value($this, 'name', $name);
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return get_value($this, 'payload');
    }

    /**
     * @param mixed $payload
     */
    public function setPayload($payload): void
    {
        set_value($this, 'payload', $payload);
    }

    public static function createFor(string $name, $payload): SubJob
    {
        $subJob = static::create();
        $subJob->setName($name);
        $subJob->setPayload($payload);

        return $subJob;
    }
}
