<?php
namespace App\Pvm\Behavior;

use App\Commands;
use App\Message\ExecuteJob;
use App\Model\JobAction;
use App\Model\PvmProcess;
use App\Model\PvmToken;
use App\Service\ChangeJobStateService;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessExecutionStorage;
use App\JobStatus;
use App\Storage\JobStorage;
use Comrade\Shared\Model\SubJobTrigger;
use Enqueue\Client\ProducerInterface;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Exception\InterruptExecutionException;
use Formapro\Pvm\Token;
use Formapro\Pvm\Transition;
use function Makasim\Values\set_value;

class RunSubJobsProcessBehavior implements Behavior
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

    /**
     * @var ChangeJobStateService
     */
    private $changeJobStateService;

    /**
     * @var ProcessExecutionStorage
     */
    private $processExecutionStorage;

    public function __construct(
        JobStorage $jobStorage,
        JobTemplateStorage $jobTemplateStorage,
        ChangeJobStateService $changeJobStateService,
        ProcessExecutionStorage $processExecutionStorage,
        ProducerInterface $producer
    )
    {
        $this->jobStorage = $jobStorage;
        $this->changeJobStateService = $changeJobStateService;
        $this->producer = $producer;
        $this->jobTemplateStorage = $jobTemplateStorage;
        $this->processExecutionStorage = $processExecutionStorage;
    }

    /**
     * @param PvmToken $token
     *
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
        $job = $this->jobStorage->getOneById($token->getJobId());
        if (JobStatus::RUNNING_SUB_JOBS !== $job->getCurrentResult()->getStatus()) {
            throw new InterruptExecutionException();
        }

        $triggers = $this->createSubJobsTriggers($token);

        $job->getRunSubJobsPolicy()->setCreatedSubJobsCount(count($triggers));
        $job->getRunSubJobsPolicy()->setFinished(false);
        $this->jobStorage->update($job);

        foreach ($triggers as $trigger) {
            $this->producer->sendCommand(Commands::EXECUTE_JOB, ExecuteJob::createFor($trigger));
        }

        return 'wait_sub_jobs';
    }

    private function findSubJobNotificationTransition(PvmProcess $process): Transition
    {
        /** @var Transition $waitSubJobsTransition */
        $waitSubJobsTransition = null;
        foreach ($process->getTransitions() as $transition) {
            if ($transition->getName() === 'sub_job_notification') {
                return $transition;
            }
        }

        throw new \LogicException('The transition with name "sub_job_notification" was not found.');
    }

    /**
     * @param PvmToken $token
     *
     * @return SubJobTrigger[]
     */
    private function createSubJobsTriggers(PvmToken $token): array
    {
        $job = $this->jobStorage->getOneById($token->getJobId());
        $subJobs = $token->getRunnerResult()->getSubJobs();

        $freshProcess = $this->processExecutionStorage->getOneByToken($token->getId());
        $subJobNotificationTransition = $this->findSubJobNotificationTransition($freshProcess);

        $triggers = [];
        foreach ($subJobs as $subJob) {
            if (false == $subJobTemplate = $this->jobTemplateStorage->findSubJobTemplate($job->getTemplateId(), $subJob->getName())) {
                throw new \LogicException(sprintf('Sub job with name "%s" for parent job  with id "%s" could not be found', $subJob->getName(), $job->getTemplateId()));
            }

            $notifyToken = $freshProcess->createToken($subJobNotificationTransition);
            set_value($notifyToken, 'notifyToken', true);

            $trigger = SubJobTrigger::create();
            $trigger->setTemplateId($subJobTemplate->getTemplateId());
            $trigger->setPayload($subJob->getPayload());
            $trigger->setParentJobId($job->getId());
            $trigger->setParentProcessId($token->getProcess()->getId());
            $trigger->setParentToken($notifyToken->getId());

            $triggers[] = $trigger;
        }

        $this->processExecutionStorage->update($freshProcess);

        return $triggers;
    }
}
