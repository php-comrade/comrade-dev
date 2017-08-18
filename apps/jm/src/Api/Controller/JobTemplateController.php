<?php
namespace App\Api\Controller;

use App\Async\AddTrigger;
use App\Async\CreateJob;
use App\Infra\JsonSchema\SchemaValidator;
use App\Service\CreateJobTemplateService;
use App\Service\ScheduleJobService;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessStorage;
use Enqueue\Util\JSON;
use Formapro\Pvm\Visual\GraphVizVisual;
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
     * @Extra\Route("/job-templates")
     * @Extra\Method("POST")
     *
     * @param Request $request
     * @param SchemaValidator $schemaValidator
     * @param CreateJobTemplateService $createJobTemplateService
     *
     * @return JsonResponse
     */
    public function createAction(Request $request, SchemaValidator $schemaValidator, CreateJobTemplateService $createJobTemplateService)
    {
        try {
            $data = JSON::decode($request->getContent());
        } catch (\Exception $e) {
            throw new BadRequestHttpException('The content is not valid json.', null, $e);
        }
        
        if ($errors = $schemaValidator->validate($data, CreateJob::SCHEMA)) {
            return new JsonResponse($errors, 400);
        }

        $createJobTemplateService->create(CreateJob::create($data)->getJobTemplate());

        return new JsonResponse('OK');
    }

    /**
     * @Extra\Route("/job-templates/{id}")
     * @Extra\Method("GET")
     *
     * @param JobTemplateStorage $jobTemplateStorage
     *
     * @return JsonResponse
     */
    public function getAction($id, JobTemplateStorage $jobTemplateStorage)
    {
        if (false == $jobTemplate = $jobTemplateStorage->findOne(['templateId' => $id])) {
            throw new NotFoundHttpException(sprintf('The job template with id "%s" could not be found', $id));
        }

        return new JsonResponse(['data' => get_values($jobTemplate)]);
    }

    /**
     * @Extra\Route("/job-templates/{id}/graph")
     * @Extra\Method("GET")
     *
     * @param $id
     * @param JobTemplateStorage $jobTemplateStorage
     * @param ProcessStorage $processStorage
     * @return Response
     */
    public function getGraphAction($id, JobTemplateStorage $jobTemplateStorage, ProcessStorage $processStorage)
    {
        if (false == $jobTemplate = $jobTemplateStorage->findOne(['templateId' => $id])) {
            throw new NotFoundHttpException(sprintf('The job template with id "%s" could not be found', $id));
        }

        $process = $processStorage->findOne(['id' => $jobTemplate->getProcessTemplateId()]);
        if (false == $process) {
            throw new NotFoundHttpException(sprintf('Process %s was not found', $id));
        }

        $graph = (new GraphVizVisual())->createGraph($process);

        return new Response(
            (new GraphViz())->createImageData($graph),
            200,
            ['Content-Type' => 'image/png']
        );
    }

    /**
     * @Extra\Route("/add-trigger")
     * @Extra\Method("POST")
     *
     * @param Request $request
     * @param SchemaValidator $schemaValidator
     * @param JobTemplateStorage $jobTemplateStorage
     * @param ScheduleJobService $scheduleJobService
     *
     * @return Response
     */
    public function addTriggerAction(Request $request, SchemaValidator $schemaValidator, JobTemplateStorage $jobTemplateStorage, ScheduleJobService $scheduleJobService)
    {
        try {
            $data = JSON::decode($request->getContent());
        } catch (\Exception $e) {
            throw new BadRequestHttpException('The content is not valid json.', null, $e);
        }

        if ($errors = $schemaValidator->validate($data, AddTrigger::SCHEMA)) {
            return new JsonResponse($errors, 400);
        }

        $addTrigger = AddTrigger::create($data);


        if (false == $jobTemplate = $jobTemplateStorage->findOne(['templateId' => $addTrigger->getJobTemplateId()])) {
            throw new NotFoundHttpException(sprintf('The job template with id "%s" could not be found', $addTrigger->getJobTemplateId()));
        }

        $trigger = $addTrigger->getTrigger();
        $jobTemplate->addTrigger($trigger);
        $jobTemplateStorage->update($jobTemplate);

        $scheduleJobService->schedule($jobTemplate, new \ArrayIterator([$trigger]));

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
            'limit' => 10,
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
}
