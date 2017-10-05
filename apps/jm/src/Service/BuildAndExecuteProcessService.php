<?php
namespace App\Service;

use App\Topics;
use App\Infra\Uuid;
use App\JobStatus;
use App\Model\JobResult;
use App\Model\PvmProcess;
use App\Storage\JobStorage;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessExecutionStorage;
use Comrade\Shared\Model\Job;
use Enqueue\Client\ProducerInterface;
use Formapro\Pvm\ProcessEngine;
use function Makasim\Values\build_object;
use function Makasim\Values\get_values;
use function Makasim\Values\set_value;
use function Makasim\Yadm\unset_object_id;
use Psr\Log\LoggerInterface;

class BuildAndExecuteProcessService
{
    /**
     * @var ProcessExecutionStorage
     */
    private $processExecutionStorage;

    /**
     * @var ProcessEngine
     */
    private $processEngine;

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
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ProcessExecutionStorage $processExecutionStorage,
        ProcessEngine $processEngine,
        JobStorage $jobStorage,
        JobTemplateStorage $jobTemplateStorage,
        ProducerInterface $producer,
        LoggerInterface $logger
    ) {
        $this->processExecutionStorage = $processExecutionStorage;
        $this->jobStorage = $jobStorage;
        $this->jobTemplateStorage = $jobTemplateStorage;
        $this->processEngine = $processEngine;
        $this->producer = $producer;
        $this->logger = $logger;
    }

    public function buildAndRun(PvmProcess $templateProcess): PvmProcess
    {
        return $this->run($this->build($templateProcess));
    }

    public function build(PvmProcess $templateProcess): PvmProcess
    {
        /** @var PvmProcess $process */
        $process = build_object(PvmProcess::class, get_values($templateProcess));

        unset_object_id($process);
        set_value($process, 'templateId', $process->getId());
        $process->setId(Uuid::generate());
        $this->processExecutionStorage->insert($process);

        if (false == $jobTemplate = $this->jobTemplateStorage->findOne(['templateId' => $process->getJobTemplateId()])) {
            throw new \LogicException(sprintf('Job template "%s" could not be found', $process->getJobTemplateId()));
        }

        $job = Job::createFromTemplate($jobTemplate);
        $job->setId(Uuid::generate());
        $job->setProcessId($process->getId());
        $job->setCreatedAt(new \DateTime('now'));

        $result = JobResult::createFor(JobStatus::NEW);
        $job->addResult($result);
        $job->setCurrentResult($result);

        $this->jobStorage->insert($job);

        $this->producer->sendEvent(Topics::JOB_UPDATED, get_values($job));

        $process->setJobId($job->getId());
        $this->processExecutionStorage->update($process);

        return $process;
    }

    public function run(PvmProcess $process): PvmProcess
    {
        try {
            foreach ($process->getTransitions() as $transition) {
                if ($transition->getFrom() === null) {
                    $token = $process->createToken($transition);

                    $this->processEngine->proceed($token, $this->logger);
                }
            }
        } finally {
            $this->processExecutionStorage->update($process);
        }

        return $process;
    }
}
