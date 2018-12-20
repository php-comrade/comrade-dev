<?php
namespace App\Api\Controller;

use App\Commands;
use App\Infra\JsonSchema\SchemaValidator;
use App\JobStatus;
use App\Service\CreateDependentJobsProcessService;
use App\JobStateMachine;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessStorage;
use App\Storage\TriggerStorage;
use Comrade\Shared\Message\CreateJob;
use Comrade\Shared\Message\GetTriggers;
use Comrade\Shared\Message\ScheduleJob;
use Comrade\Shared\Message\SearchTemplates;
use Comrade\Shared\Message\SearchTemplatesResult;
use Comrade\Shared\Model\Trigger;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Formapro\Pvm\Visual\VisualizeFlow;
use Formapro\Pvm\Visual\VisualizeStateMachine;
use Graphp\GraphViz\GraphViz;
use function Makasim\Values\get_values;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @Extra\Route("/api")
 */
class JobTemplateController
{
    /**
     * @Extra\Route("/create-job")
     * @Extra\Method("POST")
     *
     * @param Request $request
     * @param SchemaValidator $schemaValidator
     * @param ProducerInterface $producer
     *
     * @return JsonResponse
     */
    public function createAction(Request $request, SchemaValidator $schemaValidator, ProducerInterface $producer)
    {
        try {
            $data = JSON::decode($request->getContent());
        } catch (\Exception $e) {
            throw new BadRequestHttpException('The content is not valid json.', null, $e);
        }
        
        if ($errors = $schemaValidator->validate($data, CreateJob::SCHEMA)) {
            return new JsonResponse($errors, 400);
        }

        $producer->sendCommand(Commands::CREATE_JOB, $data);

        return new JsonResponse('OK');
    }

    /**
     * @Extra\Route("/job-templates/{id}")
     * @Extra\Method("GET")
     */
    public function getAction($id, JobTemplateStorage $jobTemplateStorage)
    {
        if (false == $jobTemplate = $jobTemplateStorage->findOne(['templateId' => $id])) {
            return new Response('', 404);
        }

        return new JsonResponse(['data' => get_values($jobTemplate)]);
    }

    /**
     * @Extra\Route("/search-templates")
     * @Extra\Method("POST")
     */
    public function searchAction(Request $request, SchemaValidator $schemaValidator, JobTemplateStorage $storage)
    {
        try {
            $data = JSON::decode($request->getContent());
        } catch (\Exception $e) {
            throw new BadRequestHttpException('The content is not valid json.', null, $e);
        }

        if ($errors = $schemaValidator->validate($data, SearchTemplates::SCHEMA)) {
            return new JsonResponse($errors, 400);
        }

        $query = SearchTemplates::create($data);

        $templates = $storage->find(
            ['$or' => [
                ['name' => ['$regex' => '.*'.$query->getTerm().'.*']],
                ['id' => ['$regex' => '.*'.$query->getTerm().'.*']],
            ]],
            ['limit' => $query->getLimit(), 'sort' => ['createdAt.unix' => -1]]
        );

        $result = SearchTemplatesResult::create();

        foreach ($templates as $template) {
            $result->addJobTemplate($template);
        }

        return new JsonResponse($result);
    }

    /**
     * @Extra\Route("/schedule-job")
     * @Extra\Method("POST")
     *
     * @param Request $request
     * @param SchemaValidator $schemaValidator
     * @param ProducerInterface $producer
     *
     * @return Response
     */
    public function scheduleJobAction(Request $request, SchemaValidator $schemaValidator, ProducerInterface $producer)
    {
        try {
            $data = JSON::decode($request->getContent());
        } catch (\Exception $e) {
            throw new BadRequestHttpException('The content is not valid json.', null, $e);
        }

        if ($errors = $schemaValidator->validate($data, ScheduleJob::SCHEMA)) {
            return new JsonResponse($errors, 400);
        }

        $producer->sendCommand(Commands::SCHEDULE_JOB, $data);

        return new JsonResponse('OK');
    }

    /**
     * @Extra\Route("/job-templates")
     * @Extra\Method("GET")
     *
     * @param JobTemplateStorage $jobTemplateStorage
     *
     * @return JsonResponse
     */
    public function getAllAction(JobTemplateStorage $jobTemplateStorage)
    {
        $rawJobTemplates = [];
        foreach($jobTemplateStorage->find([], [
            'limit' => 50,
            'sort' => ['createdAt.unix' => -1],
        ]) as $jobTemplate) {
            $rawJobTemplates[] = get_values($jobTemplate);
        };

        $response = new JsonResponse([
            'jobTemplates' => $rawJobTemplates
        ]);
        $response->setEncodingOptions(JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_PRETTY_PRINT);

        return $response;
    }

    /**
     * @Extra\Route("/get-triggers")
     * @Extra\Method("POST")
     *
     * @param Request $request
     * @param SchemaValidator $schemaValidator
     * @param TriggerStorage $triggerStorage
     *
     * @return JsonResponse
     */
    public function getTriggersAction(Request $request, SchemaValidator $schemaValidator, TriggerStorage $triggerStorage)
    {
        try {
            $data = JSON::decode($request->getContent());
        } catch (\Exception $e) {
            throw new BadRequestHttpException('The content is not valid json.', null, $e);
        }

        if ($errors = $schemaValidator->validate($data, GetTriggers::SCHEMA)) {
            return new JsonResponse($errors, 400);
        }

        $getTriggers = GetTriggers::create($data);

        /** @var Trigger[] $triggers */
        $triggers = $triggerStorage->find(['templateId' => $getTriggers->getTemplateId()]);

        $rawTriggers = [];
        foreach ($triggers as $trigger) {
            $rawTriggers[] = get_values($trigger);
        }

        return new JsonResponse([
            'templateId' => $getTriggers->getTemplateId(),
            'triggers' => $rawTriggers,
        ]);
    }

    /**
     * @Extra\Route("/job-template/{id}/flow-graph.gv")
     * @Extra\Method("GET")
     *
     * @param string $id
     * @param JobTemplateStorage $jobTemplateStorage
     * @param ProcessStorage $processStorage
     * @return Response
     */
    public function getFlowGraphDotAction(string $id, JobTemplateStorage $jobTemplateStorage, ProcessStorage $processStorage)
    {
        if (false == $jobTemplate = $jobTemplateStorage->findOne(['templateId' => $id])) {
            throw new NotFoundHttpException(sprintf('Job template %s was not found', $id));
        }

        $processId = $jobTemplate->getProcessTemplateId();
        if (false == $process = $processStorage->findOne(['id' => $processId])) {
            throw new NotFoundHttpException(sprintf('Process %s was not found', $processId));
        }

        $graph = (new VisualizeFlow())->createGraph($process);

        return new Response(
            (new GraphViz())->createScript($graph),
            200,
            ['Content-Type' => 'text/vnd.graphviz']
        );
    }

    /**
     * @Extra\Route("/job-template/{id}/dependent-flow-graph.gv")
     * @Extra\Method("GET")
     */
    public function getDependentFlowGraphDotAction(string $id, JobTemplateStorage $jobTemplateStorage, CreateDependentJobsProcessService $createDependentJobsProcessService): Response
    {
        if (false == $jobTemplate = $jobTemplateStorage->findOne(['templateId' => $id])) {
            throw new NotFoundHttpException(sprintf('Job template %s was not found', $id));
        }

        $process = $createDependentJobsProcessService->createProcessForJobTemplate($jobTemplate);

        $graph = (new VisualizeFlow())->createGraph($process);

        return new Response(
            (new GraphViz())->createScript($graph),
            200,
            ['Content-Type' => 'text/vnd.graphviz']
        );
    }

    /**
     * @Extra\Route("/job-template/{id}/state-graph.gv")
     * @Extra\Method("GET")
     *
     * @param string $id
     * @param JobTemplateStorage $jobTemplateStorage
     * @param ProcessStorage $processStorage
     * @return Response
     */
    public function getStateGraphDotAction(string $id, JobTemplateStorage $jobTemplateStorage, ProcessStorage $processStorage)
    {
        if (false == $jobTemplate = $jobTemplateStorage->findOne(['templateId' => $id])) {
            throw new NotFoundHttpException(sprintf('Job template %s was not found', $id));
        }

        $sm = new JobStateMachine($jobTemplate);
        $graph = (new VisualizeStateMachine())->createGraph($sm->getProcess(), JobStatus::NEW);

        return new Response(
            (new GraphViz())->createScript($graph),
            200,
            ['Content-Type' => 'text/vnd.graphviz']
        );
    }
}
