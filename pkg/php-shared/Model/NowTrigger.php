<?php
namespace Comrade\Shared\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class NowTrigger extends Trigger
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/trigger/NowTrigger.json';

    use CreateTrait;

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
}
