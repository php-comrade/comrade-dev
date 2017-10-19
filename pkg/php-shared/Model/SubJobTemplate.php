<?php
namespace Comrade\Shared\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_value;

class SubJobTemplate extends JobTemplate
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/SubJobTemplate.json';

    use CreateTrait;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @param string $id
     */
    public function setParentId(string $id):void
    {
        set_value($this, 'parentId', $id);
    }

    /**
     * @return string
     */
    public function getParentId():string
    {
        return get_value($this, 'parentId');
    }

    /**
     * @param string $parentId
     * @param JobTemplate $jobTemplate
     * @return SubJobTemplate
     */
    public static function createFromJobTemplate(string $parentId, JobTemplate $jobTemplate):SubJobTemplate
    {
        $values = get_values($jobTemplate);
        unset($values['schema']);
        $values['parentId'] = $parentId;

        return self::create($values);
    }
}
