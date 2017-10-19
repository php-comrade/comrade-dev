<?php
namespace Comrade\Shared\Model;

use Makasim\Values\CastTrait;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class SimpleTrigger extends Trigger
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/trigger/SimpleTrigger.json';

    use CreateTrait;
    use CastTrait;

    const MISFIRE_INSTRUCTION_FIRE_NOW = "fire_now";

    const MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_EXISTING_REPEAT_COUNT = "reschedule_now_with_existing_repeat_count";

    const MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_REMAINING_REPEAT_COUNT = "reschedule_now_with_remaining_repeat_count";

    const MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_REMAINING_COUNT = "reschedule_next_with_remaining_count";

    const MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_EXISTING_COUNT = "reschedule_next_with_existing_count";

    const MISFIRE_INSTRUCTION_SMART_POLICY = 'smart_policy';

    const MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY = 'ignore_misfire_policy';

    public function setStartAt(\DateTime $startAt):void
    {
        set_value($this, 'startAt', $startAt);
    }

    public function getStartAt():?\DateTime
    {
        return get_value($this, 'startAt', null, \DateTime::class);
    }

    public function setIntervalInSeconds(int $interval = null):void
    {
        set_value($this, 'intervalInSeconds', $interval);
    }

    public function getIntervalInSeconds():?int
    {
        return get_value($this, 'intervalInSeconds');
    }

    public function setRepeatCount(int $count):void
    {
        set_value($this, 'repeatCount', $count);
    }

    public function getRepeatCount():int
    {
        return get_value($this, 'repeatCount', 0);
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
