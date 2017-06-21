<?php
namespace App\Api\Controller;

use App\Async\CreateJob;
use App\Infra\JsonSchema\Errors;
use App\Infra\JsonSchema\SchemaValidator;
use App\Storage\JobTemplateStorage;
use Enqueue\Util\JSON;
use function Makasim\Values\get_values;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
     * @param JobTemplateStorage $jobTemplateStorage
     * @return JsonResponse
     */
    public function createAction(Request $request, JobTemplateStorage $jobTemplateStorage, SchemaValidator $schemaValidator)
    {
        try {
            $data = JSON::decode($request->getContent());
        } catch (\Exception $e) {
            throw new BadRequestHttpException('The content is not valid json.', null, $e);
        }


        if ($errors = $schemaValidator->validate($data, CreateJob::SCHEMA)) {
            return new JsonResponse($errors, 400);
        }

        $jobTemplate = CreateJob::create($data)->getJobTemplate();
        $processTemplate = $this->createProcessForJobService->createProcess($jobTemplate);

        $this->jobTemplateStorage->insert($jobTemplate);
        $this->processStorage->insert($processTemplate);

        if ($jobTemplate->getExclusivePolicy()) {
            $exclusiveJob = new ExclusiveJob();
            $exclusiveJob->setName($jobTemplate->getName());

            $this->exclusiveJobStorage->update($exclusiveJob, ['name' => $exclusiveJob->getName()], ['upsert' => true]);
        }

        $this->producer->sendCommand(Commands::SCHEDULE_JOB, ['jobTemplate' => $jobTemplate->getTemplateId()]);

        return self::ACK;



        return $response;
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
        foreach($jobTemplateStorage->find([]) as $jobTemplate) {
            $rawJobTemplates[] = get_values($jobTemplate);
        };

        $response = new JsonResponse([
            'jobTemplates' => $rawJobTemplates
        ]);
        $response->setEncodingOptions(JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_PRETTY_PRINT);

        return $response;
    }
}