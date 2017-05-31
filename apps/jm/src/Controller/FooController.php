<?php
namespace App\Controller;

use App\Storage\ProcessExecutionStorage;
use Formapro\Pvm\Visual\GraphVizVisual;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FooController extends Controller
{
    /**
     * @var ProcessExecutionStorage
     */
    private $processExecutionStorage;

    /**
     * @param ProcessExecutionStorage $processExecutionStorage
     */
    public function __construct(ProcessExecutionStorage $processExecutionStorage)
    {
        $this->processExecutionStorage = $processExecutionStorage;
    }

    public function graphAction($id)
    {
        $process = $this->processExecutionStorage->findOne(['id' => $id]);

        if (false == $process) {
            throw new NotFoundHttpException(sprintf('Process %s was not found', $id));
        }

        $graph = (new GraphVizVisual())->createImageSrc($process);

        return $this->render('graph.html.twig', ['graph' => $graph]);
    }
}
