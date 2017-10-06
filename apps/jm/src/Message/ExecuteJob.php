<?php
namespace App\Message;

use App\Commands;
use Comrade\Shared\Model\CreateTrait;
use Comrade\Shared\Model\Trigger;
use function Makasim\Values\get_object;
use function Makasim\Values\get_values;
use function Makasim\Values\set_object;
use function Makasim\Values\set_value;

class ExecuteJob implements \JsonSerializable
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/message/ExecuteJob.json';

    use CreateTrait;

    /**
     * @var array
     */
    private $values = [];

    public function setTrigger(Trigger $trigger): void
    {
        set_object($this, 'trigger', $trigger);
    }

    public function getTrigger(): Trigger
    {
        return get_object($this, 'trigger');
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }

    public static function createFor(Trigger $trigger): ExecuteJob
    {
        $message = static::create();
        $message->setTrigger($trigger);
        set_value($message, 'command', Commands::EXECUTE_JOB);

        return $message;
    }
}
