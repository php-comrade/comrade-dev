<?php
namespace App\Model;

use App\Infra\Yadm\CreateTrait;

class NowTrigger implements Trigger
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/trigger/NowTrigger.json';

    use CreateTrait;

    /**
     * @var array
     */
    private $values = [];
}
