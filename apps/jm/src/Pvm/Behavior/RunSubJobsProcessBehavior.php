<?php
namespace App\Pvm\Behavior;

use App\Model\RunSubJobsPolicy;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\SignalBehavior;
use Formapro\Pvm\Token;
use function Makasim\Values\get_object;

class RunSubJobsProcessBehavior implements Behavior, SignalBehavior
{
    /**
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function signal(Token $token)
    {
    }

    /**
     * @param Token $token
     *
     * @return RunSubJobsPolicy|object
     */
    private function getRunSubJobsPolicy(Token $token):RunSubJobsPolicy
    {
        return get_object($token->getTransition()->getTo(), 'runSubJobsPolicy');
    }
}
