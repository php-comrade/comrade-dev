<?php
namespace App\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class RetryFailedPolicy extends \Comrade\Shared\Model\RetryFailedPolicy
{
    public function getRetryAttempts(): int
    {
        return get_value($this, 'retryAttempts', 0);
    }

    public function incrementRetryAttempts($addition = 1): void
    {
        set_value($this, 'retryAttempts',$this->getRetryAttempts() + $addition);
    }
}
