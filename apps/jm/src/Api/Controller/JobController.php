<?php
namespace App\Api\Controller;

use App\Async\CreateJob;
use App\Infra\JsonSchema\SchemaValidator;
use App\Model\SimpleTrigger;
use App\Service\CreateJobTemplateService;
use App\Service\ScheduleJobService;
use App\Storage\JobStorage;
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
class JobController
{
    /**
     * @Extra\Route("/job-templates/{templateId}/jobs")
     * @Extra\Method("GET")
     *
     * @param Request $request
     * @param SchemaValidator $schemaValidator
     * @param CreateJobTemplateService $createJobTemplateService
     *
     * @return JsonResponse
     */
    public function createAction($templateId, JobTemplateStorage $jobTemplateStorage, JobStorage $jobStorage)
    {
        if (false == $jobTemplate = $jobTemplateStorage->findOne(['templateId' => $templateId])) {
            throw new NotFoundHttpException(sprintf('The job template with id "%s" could not be found', $templateId));
        }

        $jobs = $jobStorage->find(['templateId' => $templateId], [
            'limit' => 10,
            'sort' => ['createdAt.unix' => -1],
        ]);


        $rawJobs = [];
        foreach ($jobs as $job) {
            $rawJobs[] = get_values($job);
        }

        $response = new JsonResponse([
            'jobs' => $rawJobs
        ]);
        $response->setEncodingOptions(JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_PRETTY_PRINT);

        return $response;
    }
}