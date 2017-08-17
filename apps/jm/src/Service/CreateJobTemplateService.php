<?php
namespace App\Service;

use App\Async\Commands;
use App\Model\ExclusiveJob;
use App\Model\JobTemplate;
use App\Storage\ExclusiveJobStorage;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessStorage;
use Enqueue\Client\ProducerInterface;

class CreateJobTemplateService
{
    /**
     * @var CreateProcessForJobService
     */
    private $createProcessForJobService;

    /**
     * @var JobTemplateStorage
     */
    private $jobTemplateStorage;

    /**
     * @var ProcessStorage
     */
    private $processStorage;

    /**
     * @var ExclusiveJobStorage
     */
    private $exclusiveJobStorage;

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @param CreateProcessForJobService $createProcessForJobService
     * @param JobTemplateStorage $jobTemplateStorage
     * @param ProcessStorage $processStorage
     * @param ExclusiveJobStorage $exclusiveJobStorage
     * @param ProducerInterface $producer
     */
    public function __construct(
        CreateProcessForJobService $createProcessForJobService,
        JobTemplateStorage $jobTemplateStorage,
        ProcessStorage $processStorage,
        ExclusiveJobStorage $exclusiveJobStorage,
        ProducerInterface $producer
    ) {
        $this->createProcessForJobService = $createProcessForJobService;
        $this->jobTemplateStorage = $jobTemplateStorage;
        $this->exclusiveJobStorage = $exclusiveJobStorage;
        $this->producer = $producer;
        $this->processStorage = $processStorage;
    }

    /**
     * @param JobTemplate $jobTemplate
     */
    public function create(JobTemplate $jobTemplate):void
    {
        $jobTemplate->setCreatedAt(new \DateTime('now'));

        $processTemplate = $this->createProcessForJobService->createProcess($jobTemplate);

        $this->jobTemplateStorage->insert($jobTemplate);
        $this->processStorage->insert($processTemplate);

        if ($jobTemplate->getExclusivePolicy()) {
            $exclusiveJob = new ExclusiveJob();
            $exclusiveJob->setName($jobTemplate->getName());

            $this->exclusiveJobStorage->update($exclusiveJob, ['name' => $exclusiveJob->getName()], ['upsert' => true]);
        }

        $this->producer->sendCommand(Commands::SCHEDULE_JOB, ['jobTemplate' => $jobTemplate->getTemplateId()]);
    }
}
