<?php
namespace App\Model;

use Formapro\Pvm\Node;
use Formapro\Pvm\Process as PvmProcess;
use Formapro\Pvm\Token;
use function Makasim\Values\add_value;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class Process extends PvmProcess
{
    /**
     * @param Node $node
     * @param JobTemplate $jobTemplate
     */
    public function addNodeJobTemplate(Node $node, JobTemplate $jobTemplate):void
    {
        if ($node->getProcess() !== $this) {
            throw new \LogicException('The node is not from this processes');
        }

        set_value($node, 'jobTemplateId', $jobTemplate->getTemplateId());

        $jobTemplateIds = get_value($this, 'jobTemplateIds');
        $jobTemplateIds[] = $jobTemplate->getTemplateId();
        $jobTemplateIds = array_unique($jobTemplateIds);

        set_value($this, 'jobTemplateIds', $jobTemplateIds);
    }

    /**
     * @param Node $node
     * @param Job $job
     */
    public function addNodeJob(Node $node, Job $job):void
    {
        if ($node->getProcess() !== $this) {
            throw new \LogicException('The node is not from this processes');
        }

        set_value($node, 'jobId', $job->getId());
        add_value($this, 'jobIds', $job->getId(), $job->getId());
    }

    public function map(string $jobTemplateId, string $jobId):void
    {
        add_value($this, 'templateToJobIds', $jobId, $jobTemplateId);
        add_value($this, 'jobIds', $jobId, $jobId);
    }

    /**
     * @return \Traversable|string[]
     */
    public function getJobIds()
    {
        return get_value($this, 'jobIds', []);
    }

    /**
     * @return \Traversable|string[]
     */
    public function getJobTemplateIds()
    {
        return get_value($this, 'jobTemplateIds', []);
    }

    /**
     * @param Token $token
     *
     * @return string
     */
    public function getTokenJobId(Token $token):string
    {
        return $this->getNodeJobId($token->getTransition()->getTo());
    }

    /**
     * @param Node $node
     *
     * @return string
     */
    public function getNodeJobId(Node $node):string
    {
        if ($jobId = get_value($node, 'jobId', false)) {
            return $jobId;
        }

        $jobTemplateId = get_value($node, 'jobTemplateId');

        return get_value($node->getProcess(), 'templateToJobIds.'.$jobTemplateId);
    }
}
