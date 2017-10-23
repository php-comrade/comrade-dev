<?php
namespace Comrade\Shared\Message;

use Comrade\Shared\ClassClosure;
use Comrade\Shared\Model\JobTemplate;
use function Makasim\Values\add_object;
use function Makasim\Values\get_objects;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_value;

class RunSubJobsResult extends JobResult
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/message/RunSubJobsResult.json';

    /**
     * @return JobTemplate[]|\Traversable
     */
    public function getJobTemplates(): \Traversable
    {
        return get_objects($this,'jobTemplates', ClassClosure::create());
    }

    /**
     * @param JobTemplate $jobTemplate
     */
    public function addJobTemplate(JobTemplate $jobTemplate):void
    {
        if (false == $processTemplateId = $this->getProcessTemplateId()) {
            throw new \LogicException('The process template id must be set before calling this method');
        }

        $jobTemplate->setProcessTemplateId($processTemplateId);

        add_object($this, 'jobTemplates', $jobTemplate);
    }
    
    public function setProcessTemplateId(string $id):void
    {
        set_value($this, 'processTemplateId', $id);
    }

    public function getProcessTemplateId():string
    {
        return get_value($this, 'processTemplateId');
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }
}
