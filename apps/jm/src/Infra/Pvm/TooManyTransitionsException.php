<?php
namespace App\Infra\Pvm;

class TooManyTransitionsException extends \LogicException
{
    public static function fromNodeWithAction(string $from, string $action): TooManyTransitionsException
    {
        return new static(sprintf('There are more than one transition from the node "%s" with an action "%s". One or zero expected.', $from, $action));
    }
}