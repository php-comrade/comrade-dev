<?php
namespace App\Pvm\Behavior;

use App\Model\PvmToken;
use App\Service\PersistJobService;
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
     * @var PersistJobService
     */
    private $persistJobService;

    public function __construct(JobStorage $jobStorage, PersistJobService $persistJobService)
    {
        $this->jobStorage = $jobStorage;
        $this->persistJobService = $persistJobService;
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

        $this->persistJobService->persist($job);
    }
}
