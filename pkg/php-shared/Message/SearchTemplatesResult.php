<?php
namespace Comrade\Shared\Message;

use Comrade\Shared\ClassClosure;
use Comrade\Shared\Model\CreateTrait;
use Comrade\Shared\Model\JobTemplate;
use function Makasim\Values\add_object;
use function Makasim\Values\get_objects;
use function Makasim\Values\get_values;

class SearchTemplatesResult implements \JsonSerializable
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/message/SearchTemplatesResult.json';

    use CreateTrait;

    /**
     * @var array
     */
    protected $values = [];

    public function addJobTemplate(JobTemplate $jobTemplate): void
    {
        add_object($this,'templates', $jobTemplate);
    }

    public function getJobTemplates(): \Traversable
    {
        return get_objects($this,'templates', ClassClosure::create());
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }
}
