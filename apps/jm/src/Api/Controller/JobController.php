<?php
namespace App\Api\Controller;

use App\Commands;
use App\Infra\JsonSchema\SchemaValidator;
use App\JobStatus;
use App\Message\ExecuteJob;
use App\Model\JobResult;
use App\Service\JobStateMachine;
use App\Storage\JobStorage;
use App\Storage\JobTemplateStorage;
use App\Storage\ProcessExecutionStorage;
use Comrade\Shared\Message\GetJob;
use Comrade\Shared\Message\GetSubJobs;
use Comrade\Shared\Message\GetTimeline;
use Comrade\Shared\Model\Job;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Formapro\Pvm\Visual\VisualizeFlow;
use Formapro\Pvm\Visual\VisualizeStateMachine;
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
     * @param string $templateId
     * @param JobTemplateStorage $jobTemplateStorage
     * @param JobStorage $jobStorage
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
     * @Extra\Route("/add-job-result")
     * @Extra\Method("POST")
     *
     * @param Request $request
     * @param ProducerInterface $producer
     * @param SchemaValidator $schemaValidator
     *
     * @return Response
     */
    public function addJobResultAction(Request $request, ProducerInterface $producer, SchemaValidator $schemaValidator)
    {
        try {
            $data = JSON::decode($request->getContent());
        } catch (\Exception $e) {
            throw new BadRequestHttpException('The content is not valid json.', null, $e);
        }

        if ($errors = $schemaValidator->validate($data, \Comrade\Shared\Message\JobResult::SCHEMA)) {
            return new JsonResponse($errors, 400);
        }

        $producer->sendCommand(Commands::JOB_RESULT, $request->getContent());

        return new Response('', 204);
    }

    /**
     * @Extra\Route("/get-sub-jobs")
     * @Extra\Method("POST")
     *
     * @param Request $request
     * @param JobStorage $jobStorage
     * @param SchemaValidator $schemaValidator
     *
     * @return JsonResponse
     */
    public function getSubJobsAction(Request $request, JobStorage $jobStorage, SchemaValidator $schemaValidator)
    {
        try {
            $data = JSON::decode($request->getContent());
        } catch (\Exception $e) {
            throw new BadRequestHttpException('The content is not valid json.', null, $e);
        }

        if ($errors = $schemaValidator->validate($data, GetSubJobs::SCHEMA)) {
            return new JsonResponse($errors, 400);
        }

        $getSubJobs = GetSubJobs::create($data);

        if (false == $job = $jobStorage->findOne(['id' => $getSubJobs->getJobId()])) {
            throw new NotFoundHttpException(sprintf('The job with id "%s" could not be found', $getSubJobs->getJobId()));
        }

        $rawSubJobs = [];
        foreach ($jobStorage->find(['parentId' => $getSubJobs->getJobId()]) as $subJob) {
            $rawSubJobs[] = get_values($subJob);
        }

        return new JsonResponse([
            'subJobs' => $rawSubJobs,
        ]);
    }

    /**
     * @Extra\Route("/get-job")
     * @Extra\Method("POST")
     *
     * @param Request $request
     * @param JobStorage $jobStorage
     * @param SchemaValidator $schemaValidator
     *
     * @return JsonResponse
     */
    public function getJobAction(Request $request, JobStorage $jobStorage, SchemaValidator $schemaValidator)
    {
        try {
            $data = JSON::decode($request->getContent());
        } catch (\Exception $e) {
            throw new BadRequestHttpException('The content is not valid json.', null, $e);
        }

        if ($errors = $schemaValidator->validate($data, GetJob::SCHEMA)) {
            return new JsonResponse($errors, 400);
        }

        $getJob = GetJob::create($data);

        try {
            $job = $jobStorage->getOneById($getJob->getJobId());
        } catch (\Exception $e) {
            throw new NotFoundHttpException(sprintf('The job with id "%s" could not be found', $getJob->getJobId()), $e);
        }

        $response = new JsonResponse([
            'job' => get_values($job),
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
            $jobDataMap = $trigger->getJobDataMap();
            if (false == isset($jobDataMap['schema'])) {
                continue;
            }
            if ($jobDataMap['schema'] !== ExecuteJob::SCHEMA) {
                continue;
            }

            $executeJob = ExecuteJob::create($jobDataMap);
            if (false == $jobTemplate = $jobTemplateStorage->findOne(['templateId' => $executeJob->getTrigger()->getTemplateId()])) {
                continue;
            }

            $job = Job::create();
            $job->setName($jobTemplate->getName());
            $job->setId('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
            $job->setTemplateId($jobTemplate->getTemplateId());
            $job->setCreatedAt($trigger->getNextFireTime());
            $job->setUpdatedAt($trigger->getNextFireTime());
            $job->setPayload($jobTemplate->getPayload());

            $jobStatus = JobResult::createFor(JobStatus::NEW, $trigger->getNextFireTime());
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

    /**
     * @Extra\Route("/job/{id}/flow-graph.gv")
     * @Extra\Method("GET")
     *
     * @param string $id
     * @param JobStorage $jobStorage
     * @param ProcessExecutionStorage $processStorage
     * @return Response
     */
    public function getFlowGraphDotAction(string $id, JobStorage $jobStorage, ProcessExecutionStorage $processStorage)
    {
        if (false == $job = $jobStorage->findOne(['id' => $id])) {
            throw new NotFoundHttpException(sprintf('Job template %s was not found', $id));
        }

        $processId = $job->getProcessId();
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
     * @Extra\Route("/job/{id}/state-graph.gv")
     * @Extra\Method("GET")
     *
     * @param string $id
     * @param JobStorage $jobStorage
     * @param ProcessExecutionStorage $processStorage
     * @return Response
     */
    public function getStateGraphDotAction(string $id, JobStorage $jobStorage, ProcessExecutionStorage $processStorage)
    {
        if (false == $job = $jobStorage->findOne(['id' => $id])) {
            throw new NotFoundHttpException(sprintf('Job template %s was not found', $id));
        }

        $sm = new JobStateMachine($job);
        $graph = (new VisualizeStateMachine())->createGraph($sm->getProcess(), $job->getCurrentResult()->getStatus());

        return new Response(
            (new GraphViz())->createScript($graph),
            200,
            ['Content-Type' => 'text/vnd.graphviz']
        );
    }
}
