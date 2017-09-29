<?php
namespace App\Api\Controller;

use App\Storage\ProcessExecutionStorage;
use App\Storage\ProcessStorage;
use Formapro\Pvm\Visual\GraphVizVisual;
use Graphp\GraphViz\GraphViz;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProcessController
{
    /**
     * @Extra\Route("/process/{id}/graph.png")
     * @Extra\Method("GET")
     *
     * @param string $id
     * @param ProcessStorage $processStorage
     * @param ProcessExecutionStorage $processExecutionStorage
     * 
     * @return Response
     */
    public function getGraphImageAction(string $id, ProcessStorage $processStorage, ProcessExecutionStorage $processExecutionStorage) 
    {
        if (false == $process = $processStorage->findOne(['id' => $id])) {
            if (false == $process = $processExecutionStorage->findOne(['id' => $id])) {
                throw new NotFoundHttpException(sprintf('Process %s was not found', $id));
            }
        }

        $graph = (new GraphVizVisual())->createGraph($process);

        return new Response(
            (new GraphViz())->createImageData($graph),
            200,
            ['Content-Type' => 'image/png']
        );
    }

    /**
     * @Extra\Route("/process/{id}/graph.gv")
     * @Extra\Method("GET")
     *
     * @param string $id
     * @param ProcessStorage $processStorage
     * @param ProcessExecutionStorage $processExecutionStorage
     *
     * @return Response
     */
    public function getGraphDotAction(string $id, ProcessStorage $processStorage, ProcessExecutionStorage $processExecutionStorage)
    {
        if (false == $process = $processStorage->findOne(['id' => $id])) {
            if (false == $process = $processExecutionStorage->findOne(['id' => $id])) {
                throw new NotFoundHttpException(sprintf('Process %s was not found', $id));
            }
        }

        $graph = (new GraphVizVisual())->createGraph($process);

        return new Response(
            (new GraphViz())->createScript($graph),
            200,
            ['Content-Type' => 'text/vnd.graphviz']
        );
    }
}
