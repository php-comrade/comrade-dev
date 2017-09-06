<?php

namespace App\Api\Controller;

use App\Chart\GetJobChart;
use App\Infra\JsonSchema\SchemaValidator;
use App\JobStatus;
use App\Storage\JobMetricsStorage;
use Enqueue\Util\JSON;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Extra\Route("/api")
 */
class MetricsController
{
    /**
     * @Extra\Route("/metrics/global")
     * @Extra\Method("GET")
     *
     * @return JsonResponse
     */
    public function globalMetricsAction(JobMetricsStorage $metricsStorage)
    {
        $response = new JsonResponse([
            'metrics' => [
                'successJobsLastMinute' => $metricsStorage->countJobsPerPeriod(new \DateTime('-1 minute'), new \DateTime(), [JobStatus::STATUS_COMPLETED]),
                'successJobsLastHour' => $metricsStorage->countJobsPerPeriod(new \DateTime('-1 hour'), new \DateTime(), [JobStatus::STATUS_COMPLETED]),
                'successJobsLastDay' => $metricsStorage->countJobsPerPeriod(new \DateTime('today'), new \DateTime(), [JobStatus::STATUS_COMPLETED]),
                'failedJobsLastHour' => $metricsStorage->countJobsPerPeriod(new \DateTime('-1 hour'), new \DateTime(), [JobStatus::STATUS_FAILED]),
            ],
        ]);
        $response->setEncodingOptions(JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_PRETTY_PRINT);

        return $response;
    }

    /**
     * @Extra\Route("/metrics/chart")
     * @Extra\Method("GET|POST")
     *
     * @return JsonResponse
     */
    public function chartAction(Request $request, JobMetricsStorage $metricsStorage, SchemaValidator $schemaValidator)
    {
        try {
            $data = JSON::decode($request->getContent());
        } catch (\Exception $e) {
            throw new BadRequestHttpException('The content is not valid json.', $e);
        }

        if ($errors = $schemaValidator->validate($data, GetJobChart::SCHEMA)) {
            return new JsonResponse($errors, 400);
        }

        $chartRequest = GetJobChart::create($data);

        $result = $metricsStorage->chart(
            $chartRequest->getSince(),
            $chartRequest->getUntil(),
            null,
            $chartRequest->getStatuses(),
            $chartRequest->getTemplateId()
        );

        $response = new JsonResponse($result);
        $response->setEncodingOptions(JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_PRETTY_PRINT);

        return $response;
    }
}
