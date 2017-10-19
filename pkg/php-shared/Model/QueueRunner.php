<?php
namespace Comrade\Shared\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class QueueRunner implements Runner
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/runner/QueueRunner.json';

    use CreateTrait;

    /**
     * @var array
     */
    protected $values = [];

    public function setQueue(string $queue):void
    {
        set_value($this, 'queue', $queue);
    }

    public function getQueue():string
    {
        return get_value($this, 'queue');
    }

    public function setConnectionDsn(string $connectionDsn = null):void
    {
        set_value($this, 'connectionDsn', $connectionDsn);
    }

    public function getConnectionDsn():?string
    {
        return get_value($this, 'connectionDsn');
    }

    /**
     * @param string $queueName
     *
     * @return static
     */
    public static function createFor(string $queueName)
    {
        return static::create([
            'queue' => $queueName,
        ]);
    }
}
