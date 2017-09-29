<?php
namespace Comrade\Shared\Model;

class NowTrigger implements Trigger
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/trigger/NowTrigger.json';

    use CreateTrait;

    /**
     * @var array
     */
    private $values = [];
}
