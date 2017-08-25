<?php
namespace App\Api\Controller;

use App\Infra\Error\ErrorStorage;
use Enqueue\Util\JSON;
use function Makasim\Values\get_values;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Extra\Route("/api/errors")
 */
class ErrorController
{
    /**
     * @Extra\Route("/late")
     * @Extra\Method("GET")
     *
     * @param ErrorStorage $errorStorage
     *
     * @return JsonResponse
     */
    public function getLateErrorsAction(ErrorStorage $errorStorage)
    {
        $totalNumber = $errorStorage->count([]);

        $errors = $errorStorage->find([], [
            'limit' => 50,
            'sort' => ['createdAt' => -1],
        ]);

        $rawErrors = [];
        foreach ($errors as $error) {
            $rawErrors[] = get_values($error);
        }

        return new JsonResponse([
            'errors' => $rawErrors,
            'totalNumber' => $totalNumber,
        ]);
    }

    /**
     * @Extra\Route("")
     * @Extra\Method("DELETE")
     *
     * @param ErrorStorage $errorStorage
     *
     * @return JsonResponse
     */
    public function deleteErrorsAction(Request $request, ErrorStorage $errorStorage)
    {
        if ($olderMicroSeconds = $request->query->get('older', false)) {
            $errorStorage->getCollection()->deleteMany(['createdAt' => ['$lt' => (int) $olderMicroSeconds]]);
        }

        if ($request->query->get('all', false)) {
            $errorStorage->getCollection()->deleteMany([]);
        }

        return new JsonResponse([]);
    }
}
