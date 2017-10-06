<?php
namespace App\Pvm\Behavior;

use App\Model\PvmToken;
use App\Storage\JobStorage;
use App\Topics;
use Enqueue\Client\ProducerInterface;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Exception\InterruptExecutionException;
use Formapro\Pvm\Token;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_value;

class StartJobBehavior implements Behavior
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var ProducerInterface
     */
    private $producer;

    public function __construct(JobStorage $jobStorage, ProducerInterface $producer)
    {
        $this->jobStorage = $jobStorage;
        $this->producer = $producer;
    }

    /**
     * @param PvmToken $token
     *
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
        $job = $this->jobStorage->getOneById($token->getJobId());

        if (get_value($job,'startAt')) {
            throw new InterruptExecutionException();
        }

        set_value($job, 'startAt', new \DateTime('now'));

        $this->jobStorage->update($job);

        $this->producer->sendEvent(Topics::JOB_UPDATED, get_values($job));
    }
}
