<?php
namespace App\Model;

use App\Infra\Yadm\CreateTrait;
use Makasim\Values\CastTrait;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class GracePeriodPolicy implements Policy
{
    use CastTrait;
    use CreateTrait;

    const SCHEMA = 'http://jm.forma-pro.com/schemas/gracePeriodPolicy.json';

    private $values = [];

    /**
     * @param \DateTime $date
     */
    public function setPeriodEndsAt(\DateTime $date)
    {
        set_value($this, 'periodEndsAt', $date);
    }

    /**
     * @return \DateTime|null
     */
    public function getPeriodEndsAt(): ?\DateTime
    {
        return get_value($this, 'periodEndsAt', null, \DateTime::class);
    }
}