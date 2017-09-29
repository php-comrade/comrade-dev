<?php
namespace Comrade\Shared\Message;

use Comrade\Shared\Model\CreateTrait;
use Comrade\Shared\Model\JobTemplate;
use function Makasim\Values\get_object;
use function Makasim\Values\get_values;
use function Makasim\Values\set_object;

class CreateJob implements \JsonSerializable
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/message/CreateJob.json';

    use CreateTrait;

    /**
     * @var array
     */
    private $values = [];

    /**
     * @return JobTemplate|object
     */
    public function getJobTemplate(): JobTemplate
    {
        return get_object($this,'jobTemplate');
    }

    /**
     * @param JobTemplate $jobTemplate
     */
    public function setJobTemplate(JobTemplate $jobTemplate): void
    {
        set_object($this, 'jobTemplate', $jobTemplate);
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
