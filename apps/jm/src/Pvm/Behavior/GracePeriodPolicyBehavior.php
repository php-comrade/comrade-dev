<?php
namespace App\Pvm\Behavior;

use App\Model\GracePeriodPolicy;
use App\Model\Process;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Token;
use function Makasim\Values\get_object;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class GracePeriodPolicyBehavior implements Behavior
{
    /**
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
        /** @var Process $process */
        $process = $token->getProcess();

        /** @var GracePeriodPolicy $gracePeriodPolicy */
        $gracePeriodPolicy = get_object($token->getTransition()->getTo(), 'gracePeriodPolicy');
        $endsAt = $gracePeriodPolicy->getPeriodEndsAt()->getTimestamp();
        $job = $process->getJob(get_value($token->getTransition()->getTo(), 'job.uid'));

        // TODO remove debug
        $endsAt = time() + 60;
        while (time() < $endsAt) {
            sleep(1);
        }

        set_value($job, 'timeoutAt', time());
    }
}
