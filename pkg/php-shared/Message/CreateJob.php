<?php
namespace Comrade\Shared\Message;

use Comrade\Shared\ClassClosure;
use Comrade\Shared\Model\CreateTrait;
use Comrade\Shared\Model\JobTemplate;
use Comrade\Shared\Model\Trigger;
use function Makasim\Values\add_object;
use function Makasim\Values\get_object;
use function Makasim\Values\get_objects;
use function Makasim\Values\get_values;
use function Makasim\Values\set_object;
use function Makasim\Values\set_value;

class CreateJob implements \JsonSerializable
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/message/CreateJob.json';

    use CreateTrait;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @return JobTemplate|object
     */
    public function getJobTemplate(): JobTemplate
    {
        return get_object($this,'jobTemplate', ClassClosure::create());
    }

    /**
     * @param JobTemplate $jobTemplate
     */
    public function setJobTemplate(JobTemplate $jobTemplate): void
    {
        set_object($this, 'jobTemplate', $jobTemplate);
    }

    public function addTrigger(Trigger $trigger): void
    {
        add_object($this, 'triggers', $trigger);
    }

    /**
     * @return \Traversable|Trigger[]
     */
    public function getTriggers(): \Traversable
    {
        return get_objects($this, 'triggers', ClassClosure::create());
    }

    /**
     * @return void
     */
    public function removeTriggers(): void
    {
        set_value($this, 'triggers', null);
    }

    public static function createFor(JobTemplate $jobTemplate): CreateJob
    {
        $message = static::create();
        $message->setJobTemplate($jobTemplate);

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }
}
