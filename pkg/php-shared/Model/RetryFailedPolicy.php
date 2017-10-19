<?php
namespace Comrade\Shared\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class RetryFailedPolicy implements Policy
{
    use CreateTrait;

    const SCHEMA = 'http://comrade.forma-pro.com/schemas/policy/RetryFailedPolicy.json';

    protected $values = [];

    /**
     * @param int $retryLimit
     */
    public function setRetryLimit(int $retryLimit)
    {
        set_value($this, 'retryLimit', $retryLimit);
    }

    /**
     * @return int|null
     */
    public function getRetryLimit(): ?int
    {
        return get_value($this, 'retryLimit');
    }
}
