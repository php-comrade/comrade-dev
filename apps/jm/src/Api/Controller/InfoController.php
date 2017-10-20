<?php
namespace App\Api\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\HttpFoundation\Request;

class InfoController
{
    /**
     * @Extra\Route("/api/info")
     * @Extra\Route("/")
     * @Extra\Method("GET")
     */
    public function infoAction(Request $request, ContainerInterface $container)
    {
        $projectDir = $container->getParameter('kernel.project_dir');

        $version = 'unknown';
        if (file_exists($projectDir.'/config/version')) {
            $version = trim(file_get_contents($projectDir.'/config/version'));
        }

        $build = 'unknown';
        if (file_exists($projectDir.'/config/build')) {
            $build = trim(file_get_contents($projectDir.'/config/build'));
        }

        return new JsonResponse([
            'title' => 'I am your comrade',
            'uri' => $request->getUri(),
            'version' => $version,
            'build' => $build,
        ]);
    }
}
