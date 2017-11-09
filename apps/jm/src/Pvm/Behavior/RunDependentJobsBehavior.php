<?php
namespace App\Pvm\Behavior;

use App\Commands;
use App\Message\ExecuteJob;
use App\Model\PvmToken;
use App\Storage\JobTemplateStorage;
use App\Storage\JobStorage;
use Comrade\Shared\Model\DependentJobTrigger;
use Comrade\Shared\Model\NowTrigger;
use Enqueue\Client\ProducerInterface;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Token;

class RunDependentJobsBehavior implements Behavior
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var JobTemplateStorage
     */
    private $jobTemplateStorage;

    /**
     * @var ProducerInterface
     */
    private $producer;

    public function __construct(
        JobStorage $jobStorage,
        JobTemplateStorage $jobTemplateStorage,
        ProducerInterface $producer
    )
    {
        $this->jobStorage = $jobStorage;
        $this->producer = $producer;
        $this->jobTemplateStorage = $jobTemplateStorage;
    }

    /**
     * @param PvmToken $token
     *
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
        $job = $this->jobStorage->getOneById($token->getJobId());
        $triggers = [];
        foreach ($job->getRunDependentJobPolicies() as $policy) {
            if (false == $subJobTemplate = $this->jobTemplateStorage->findOne(['templateId' => $policy->getTemplateId()])) {
                throw new \LogicException(sprintf('The job  template with id "%s" could not be found', $policy->getTemplateId()));
            }

            if ($policy->isRunAlways() || in_array($job->getCurrentResult()->getStatus(), $policy->getRunOnStatus())) {
                $trigger = DependentJobTrigger::create();
                $trigger->setParentJobId($job->getId());
                $trigger->setTemplateId($policy->getTemplateId());
                $trigger->setPayload($job->getResultPayload());

                $triggers[] = $trigger;
            }
        }

        foreach ($triggers as $trigger) {
            $this->producer->sendCommand(Commands::EXECUTE_JOB, ExecuteJob::createFor($trigger));
        }
    }
}
