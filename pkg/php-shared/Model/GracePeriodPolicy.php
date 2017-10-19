<?php
namespace Comrade\Shared\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class GracePeriodPolicy implements Policy
{
    use CreateTrait;

    const SCHEMA = 'http://comrade.forma-pro.com/schemas/policy/GracePeriodPolicy.json';

    protected $values = [];

    public function setPeriod(int $period):void
    {
        set_value($this, 'period', $period);
    }

    public function getPeriod(): int
    {
        return get_value($this, 'period');
    }
}