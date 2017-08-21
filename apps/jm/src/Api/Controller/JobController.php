<?php
namespace App\Api\Controller;

use App\Async\CreateJob;
use App\Async\GetTimeline;
use App\Infra\JsonSchema\SchemaValidator;
use App\JobStatus;
use App\Model\Job;
use App\Model\JobResult;
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
use Quartz\Core\Trigger;
use Quartz\Scheduler\Store\YadmStoreResource;
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
    public function getJobsAction($templateId, JobTemplateStorage $jobTemplateStorage, JobStorage $jobStorage)
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

    /**
     * @Extra\Route("/jobs/timeline-done")
     * @Extra\Method("POST")
     *
     * @param Request $request
     * @param JobStorage $jobStorage
     * @param SchemaValidator $schemaValidator
     *
     * @return JsonResponse
     */
    public function timelineDoneAction(Request $request, JobStorage $jobStorage, SchemaValidator $schemaValidator)
    {
        try {
            $data = JSON::decode($request->getContent());
        } catch (\Exception $e) {
            throw new BadRequestHttpException('The content is not valid json.', null, $e);
        }

        if ($errors = $schemaValidator->validate($data, GetTimeline::SCHEMA)) {
            return new JsonResponse($errors, 400);
        }

        $getTimeline = GetTimeline::create($data);

        $filter = [];
        if ($templateId = $getTimeline->getJobTemplateId()) {
            $filter['templateId'] = $templateId;
        }

        $jobs = $jobStorage->find($filter, [
            'limit' => $getTimeline->getLimit(),
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

    /**
     * @Extra\Route("/jobs/timeline-future")
     * @Extra\Method("POST")
     *
     * @param Request $request
     * @param YadmStoreResource $qartzStoreResource
     * @param SchemaValidator $schemaValidator
     *
     * @return JsonResponse
     */
    public function timelineFutureAction(
        Request $request,
        YadmStoreResource $qartzStoreResource,
        SchemaValidator $schemaValidator,
        JobTemplateStorage $jobTemplateStorage
    ) {
        try {
            $data = JSON::decode($request->getContent());
        } catch (\Exception $e) {
            throw new BadRequestHttpException('The content is not valid json.', null, $e);
        }

        if ($errors = $schemaValidator->validate($data, GetTimeline::SCHEMA)) {
            return new JsonResponse($errors, 400);
        }

        $getTimeline = GetTimeline::create($data);

        $filter = [];
        $filter['nextFireTime.unix'] = ['$gte' => time() + 5];
        if ($templateId = $getTimeline->getJobTemplateId()) {
            $filter['jobDataMap.jobTemplate'] = $templateId;
        }

        /** @var Trigger[] $triggers */
        $triggers = $qartzStoreResource->getTriggerStorage()->find($filter, [
            'limit' => $getTimeline->getLimit(),
            'sort' => ['nextFireTime.unix' => -1],
        ]);
        $rawJobs = [];
        foreach ($triggers as $trigger) {
            if (false == $jobTemplate = $jobTemplateStorage->findOne(['templateId' => $trigger->getJobDataMap()['jobTemplate']])) {
                continue;
            }

            $job = Job::create();
            $job->setName($jobTemplate->getName());
            $job->setId('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
            $job->setTemplateId($jobTemplate->getTemplateId());
            $job->setCreatedAt($trigger->getNextFireTime());
            $job->setDetails($jobTemplate->getDetails());

            $jobStatus = JobResult::createFor(JobStatus::STATUS_NEW, $trigger->getNextFireTime());
            $job->setCurrentResult($jobStatus);
            $job->addResult($jobStatus);

            $rawJobs[] = get_values($job);
        }

        $response = new JsonResponse([
            'jobs' => $rawJobs
        ]);
        $response->setEncodingOptions(JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_PRETTY_PRINT);

        return $response;
    }
}
