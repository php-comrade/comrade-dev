<?php
namespace App\Model;

use Formapro\Pvm\Node;
use Formapro\Pvm\Process as PvmProcess;
use Formapro\Pvm\Token;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class Process extends PvmProcess
{
    /**
     * @param Node $node
     * @param Job $job
     */
    public function setNodeJob(Node $node, Job $job):void
    {
        set_value($node, 'jobId', $job->getId());
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
        return get_value($node, 'jobId');
    }
}
