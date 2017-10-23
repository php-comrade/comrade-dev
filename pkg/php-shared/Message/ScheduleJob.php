<?php
namespace Comrade\Shared\Message;

use Comrade\Shared\ClassClosure;
use Comrade\Shared\Model\CreateTrait;
use Comrade\Shared\Model\Trigger;
use function Makasim\Values\get_object;
use function Makasim\Values\get_values;
use function Makasim\Values\set_object;

class ScheduleJob implements \JsonSerializable
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/message/ScheduleJob.json';

    use CreateTrait;

    /**
     * @var array
     */
    protected $values = [];

    public function setTrigger(Trigger $trigger): void
    {
        set_object($this, 'trigger', $trigger);
    }

    public function getTrigger(): Trigger
    {
        return get_object($this, 'trigger', ClassClosure::create());
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }

    public static function createFor(Trigger $trigger): ScheduleJob
    {
        $message = static::create();
        $message->setTrigger($trigger);

        return $message;
    }
}
