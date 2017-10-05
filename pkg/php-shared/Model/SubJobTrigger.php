<?php
namespace Comrade\Shared\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class SubJobTrigger extends Trigger
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/trigger/SubJobTrigger.json';

    use CreateTrait;

    public function getParentTemplateId(): string
    {
        return get_value($this,'parentTemplateId');
    }

    public function setParentTemplateId(string $id): void
    {
        set_value($this, 'parentTemplateId', $id);
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return get_value($this, 'payload');
    }

    /**
     * @param mixed $payload
     */
    public function setPayload($payload): void
    {
        set_value($this, 'payload', $payload);
    }

    public static function createFor(JobTemplate $jobTemplate, $payload): SubJobTrigger
    {
        if (false == $jobTemplate->getSubJobPolicy()) {
            throw new \LogicException(sprintf('The given jobTemplate "%s" is not sub job one.', $jobTemplate->getTemplateId()));
        }

        $trigger = static::create();
        $trigger->setTemplateId($jobTemplate->getTemplateId());
        $trigger->setParentTemplateId($jobTemplate->getSubJobPolicy()->getParentId());
        $trigger->setPayload($payload);

        return $trigger;
    }
}
