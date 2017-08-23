<?php
namespace App\Async;

use App\Model\JobTemplate;
use function Makasim\Values\add_object;
use function Makasim\Values\get_objects;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_value;

class RunSubJobsResult extends JobResult
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/message/RunSubJobsResult.json';

    /**
     * @return JobTemplate[]|\Traversable
     */
    public function getJobTemplates():\Traversable
    {
        return get_objects($this,'jobTemplates');
    }

    /**
     * @param JobTemplate $jobTemplate
     */
    public function addJobTemplate(JobTemplate $jobTemplate):void
    {
        // the message's process template id must be used at server. 
        $jobTemplate->setProcessTemplateId('ffffffff-ffff-ffff-ffff-ffffffffffff');
        
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
