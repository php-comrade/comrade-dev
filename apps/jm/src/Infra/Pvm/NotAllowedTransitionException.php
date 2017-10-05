<?php
namespace App\Infra\Pvm;

class NotAllowedTransitionException extends \LogicException
{
    public static function fromNodeWithAction(string $from, string $action): NotAllowedTransitionException
    {
        return new static(sprintf('The transition from the node "%s" with an action "%s" is not allowed.', $from, $action));
    }
}