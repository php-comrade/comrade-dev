<?php
namespace App\Controller;

use App\Model\Process;
use App\Storage\ProcessExecutionStorage;
use Formapro\Pvm\CallbackBehavior;
use Formapro\Pvm\DefaultBehaviorRegistry;
use Formapro\Pvm\EchoBehavior;
use Formapro\Pvm\Exception\InterruptExecutionException;
use Formapro\Pvm\Exception\WaitExecutionException;
use Formapro\Pvm\ProcessEngine;
use Formapro\Pvm\Token;
use Formapro\Pvm\Visual\GraphVizVisual;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;
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
