<?php
namespace Comrade\Shared\Message;

use Comrade\Shared\Model\CreateTrait;
use Comrade\Shared\Model\JobTemplate;
use Comrade\Shared\Model\Trigger;
use function Makasim\Values\get_objects;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_objects;
use function Makasim\Values\set_value;

class ScheduleJob implements \JsonSerializable
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/message/ScheduleJob.json';

    use CreateTrait;

    /**
     * @var array
     */
    private $values = [];

    /**
     * @return string
     */
    public function getJobTemplateId(): string
    {
        return get_value($this,'jobTemplateId');
    }

    /**
     * @param string $id
     */
    public function setJobTemplateId(string $id):void
    {
        set_value($this, 'jobTemplateId', $id);
    }

    /**
     * @param \Traversable|Trigger[] $triggers
     *
     * @return void
     */
    public function setTriggers(\Traversable $triggers):void
    {
        set_objects($this, 'triggers', iterator_to_array($triggers));
    }

    /**
     * @return \Traversable|Trigger[]
     */
    public function getTriggers():\Traversable
    {
        return get_objects($this, 'triggers');
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }

    public static function createFor(JobTemplate $jobTemplate):ScheduleJob
    {
        $message = static::create();
        $message->setJobTemplateId($jobTemplate->getTemplateId());
        $message->setTriggers($jobTemplate->getTriggers());

        return $message;
    }

    public static function createForSingle(JobTemplate $jobTemplate, Trigger $trigger):ScheduleJob
    {
        $message = static::create();
        $message->setJobTemplateId($jobTemplate->getTemplateId());
        $message->setTriggers(new \ArrayObject([$trigger]));

        return $message;
    }
}
