<?php
namespace App\Model;

use App\Infra\Yadm\CreateTrait;
use Makasim\Values\CastTrait;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class CronTrigger implements Trigger
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/trigger/CronTrigger.json';

    const MISFIRE_INSTRUCTION_FIRE_ONCE_NOW = 'fire_once_now';

    const MISFIRE_INSTRUCTION_DO_NOTHING = "do_nothing";

    use CreateTrait;
    use CastTrait;

    /**
     * @var array
     */
    private $values = [];

    public function setExpression(string $expression):void
    {
        set_value($this, 'expression', $expression);
    }

    public function getExpression():string
    {
        return get_value($this, 'expression');
    }

    public function setMisfireInstruction(string $instruction):void
    {
        set_value($this, 'misfireInstruction', $instruction);
    }

    public function getMisfireInstruction():string
    {
        return get_value($this, 'misfireInstruction');
    }
}
