<?php
namespace App\Pvm\Behavior;

use App\Async\Topics;
use App\Model\Job;
use App\Model\JobResult;
use App\Model\Process;
use App\Model\RunSubJobsPolicy;
use App\Service\CreateProcessForSubJobsService;
use App\Storage\JobStorage;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessStorage;
use Enqueue\Client\ProducerInterface;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Exception\WaitExecutionException;
use Formapro\Pvm\SignalBehavior;
use Formapro\Pvm\Token;
use function Makasim\Values\get_object;

class RunSubJobsProcessBehavior implements Behavior, SignalBehavior
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
     * @var ProcessStorage
     */
    private $processStorage;

    /**
     * @var CreateProcessForSubJobsService
     */
    private $createProcessForSubJobsService;

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @param JobStorage $jobStorage
     * @param JobTemplateStorage $jobTemplateStorage
     * @param ProcessStorage $processStorage
     * @param CreateProcessForSubJobsService $createProcessForSubJobsService
     * @param ProducerInterface $producer
     */
    public function __construct(
        JobStorage $jobStorage,
        JobTemplateStorage $jobTemplateStorage,
        ProcessStorage $processStorage,
        CreateProcessForSubJobsService $createProcessForSubJobsService,
        ProducerInterface $producer
    ) {
        $this->jobStorage = $jobStorage;
        $this->jobTemplateStorage = $jobTemplateStorage;
        $this->processStorage = $processStorage;
        $this->createProcessForSubJobsService = $createProcessForSubJobsService;
        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
        /** @var Process $process */
        $process = $token->getProcess();
        $job = $this->jobStorage->getOneById($process->getTokenJobId($token));

        if (false == $job->getCurrentResult()->isRunSubJobs()) {
            throw new \LogicException('The process comes to this task but its status is not "run_sub_jobs"');
        }

        $jobTemplates = iterator_to_array($this->jobTemplateStorage->find(['parentId' => $job->getId()]));
        if (false == $jobTemplates) {
            $jobResult = JobResult::createFor(Job::STATUS_COMPLETED);
            $job->addResult($jobResult);
            $job->setCurrentResult($jobResult);
            $this->jobStorage->update($job);

            return;
        }

        $jobResult = JobResult::createFor(Job::STATUS_RUNNING_SUB_JOBS);
        $job->addResult($jobResult);
        $job->setCurrentResult($jobResult);
        $this->jobStorage->update($job);

        $subProcess = $this->createProcessForSubJobsService->createProcess($token, $jobTemplates);
        $this->processStorage->insert($subProcess);

        $this->producer->send(Topics::SCHEDULE_PROCESS, $subProcess->getId());

        throw new WaitExecutionException();
    }

    /**
     * {@inheritdoc}
     */
    public function signal(Token $token)
    {
        /** @var Process $process */
        $process = $token->getProcess();
        $job = $this->jobStorage->getOneById($process->getTokenJobId($token));

        $jobResult = JobResult::createFor(Job::STATUS_COMPLETED);
        $job->addResult($jobResult);
        $job->setCurrentResult($jobResult);

        $this->jobStorage->update($job);

        return ['completed'];
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
