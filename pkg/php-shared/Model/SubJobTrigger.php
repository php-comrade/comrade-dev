<?php
namespace Comrade\Shared\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class SubJobTrigger extends Trigger
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/trigger/SubJobTrigger.json';

    use CreateTrait;

    public function getParentJobId(): string
    {
        return get_value($this,'parentJobId');
    }

    public function setParentJobId(string $id): void
    {
        set_value($this, 'parentJobId', $id);
    }

    public function getParentProcessId(): string
    {
        return get_value($this,'parentProcessId');
    }

    public function setParentProcessId(string $id): void
    {
        set_value($this, 'parentProcessId', $id);
    }

    public function getParentToken(): string
    {
        return get_value($this,'parentToken');
    }

    public function setParentToken(string $id): void
    {
        set_value($this, 'parentToken', $id);
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
}
