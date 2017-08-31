<?php
namespace App\Api\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

class InfoController
{
    /**
     * @Extra\Route("/api/info")
     * @Extra\Method("GET")
     */
    public function infoAction()
    {
        return new JsonResponse([
            'title' => 'I am your comrade',
        ]);
    }
}
