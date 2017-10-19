<?php
namespace Comrade\Shared\Model;

use Makasim\Values\CastTrait;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class CronTrigger extends Trigger
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/trigger/CronTrigger.json';

    const MISFIRE_INSTRUCTION_FIRE_ONCE_NOW = 'fire_once_now';

    const MISFIRE_INSTRUCTION_DO_NOTHING = "do_nothing";

    const MISFIRE_INSTRUCTION_SMART_POLICY = 'smart_policy';

    const MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY = 'ignore_misfire_policy';

    use CreateTrait;
    use CastTrait;

    public function setStartAt(\DateTime $startAt):void
    {
        set_value($this, 'startAt', $startAt);
    }

    public function getStartAt():?\DateTime
    {
        return get_value($this, 'startAt', null, \DateTime::class);
    }

    public function setExpression(string $expression):void
    {
        set_value($this, 'expression', $expression);
    }

    public function getExpression():string
    {
        return get_value($this, 'expression');
    }

    /**
     * Quartz uses extended cron format which includes seconds hence there are six * instead of five in original cron.
     * The method adopts cron expression so it match quartz requirement.
     */
    public function getQuartzExpression():string
    {
        $expression = $this->getExpression();

        if (preg_match('/^.*? .*? .*? .*? .*?$/', $expression)) {
            $expression = "0 $expression";
        }

        return $expression;
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
