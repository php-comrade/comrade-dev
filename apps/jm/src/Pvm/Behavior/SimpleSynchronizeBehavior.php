<?php
namespace App\Pvm\Behavior;

use App\Model\Process;
use App\Storage\ProcessExecutionStorage;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Exception\InterruptExecutionException;
use Formapro\Pvm\Token;
use function Makasim\Values\build_object;
use function Makasim\Values\get_value;
use MongoDB\Operation\FindOneAndUpdate;

class SimpleSynchronizeBehavior implements Behavior
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

    /**
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
        $process = $token->getProcess();
        $node = $token->getTransition()->getTo();

        $rawRefreshedProcess = $this->processExecutionStorage->getCollection()->findOneAndUpdate(
            ['id' => $process->getId()],
            ['$inc' => ['nodes.'.$node->getId().'.currentWeight' => $token->getTransition()->getWeight()]],
            [
                'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
                'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
            ]
        );

        /** @var Process $refreshedProcess */
        $refreshedProcess = build_object(Process::class, $rawRefreshedProcess);
        $refreshedNode = $refreshedProcess->getNode($node->getId());

        if (get_value($refreshedNode, 'currentWeight') != get_value($refreshedNode, 'requiredWeight')) {
            throw new InterruptExecutionException();
        }

        // continue execution.
    }
}
