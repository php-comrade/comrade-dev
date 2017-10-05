<?php

namespace App\Infra\Pvm;

use Formapro\Pvm\Node;
use Formapro\Pvm\Process;
use Formapro\Pvm\Transition;

class StateMachine
{
    /**
     * @var Process
     */
    private $process;

    /**
     * @param Process $process
     */
    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    public function getProcess(): Process
    {
        return $this->process;
    }

    public function can(string $node, string $action): ?Transition
    {
        $transitions = $this->process->getOutTransitionsWithName($this->findNode($node), $action);
        if (count($transitions) > 1) {
            throw TooManyTransitionsException::fromNodeWithAction($node, $action);
        }

        return count($transitions) ? $transitions[0] : null;
    }

    /**
     * @param string $node
     *
     * @return Transition[]
     */
    public function getTransitions(string $node): array
    {
        $transitions = [];
        foreach ($this->process->getOutTransitions($this->findNode($node)) as $transition) {
            $transitions[] = $transition->getName();
        }

        return $transitions;
    }

    /**
     * @param string $node
     *
     * @return string[]
     */
    public function getActions(string $node): array
    {
        $actions = [];
        foreach ($this->getTransitions($node) as $transition) {
            $actions[] = $transition->getName();
        }

        return $actions;
    }

    private function findNode(string $name): Node
    {
        /** @var Node $node */
        foreach ($this->process->getNodes() as $node) {
            if ($node->getLabel() === $name) {
                return $node;
            }
        }

        throw new \LogicException(sprintf('Could not find node: "%s"', $name));
    }
}
