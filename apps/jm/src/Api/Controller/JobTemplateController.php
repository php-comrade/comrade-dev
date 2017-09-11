<?php
namespace App\Api\Controller;

use App\Async\AddTrigger;
use App\Async\Commands;
use App\Async\CreateJob;
use App\Async\ScheduleJob;
use App\Infra\JsonSchema\SchemaValidator;
use App\Service\ScheduleJobService;
use App\Storage\JobTemplateStorage;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
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
    public function addTriggerAction(Request $request, SchemaValidator $schemaValidator, JobTemplateStorage $jobTemplateStorage, ProducerInterface $producer)
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

        $producer->sendCommand(Commands::SCHEDULE_JOB, ScheduleJob::createForSingle($jobTemplate, $trigger));

        return new JsonResponse([
            'jobTemplate' => get_values($jobTemplate),
        ]);
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
