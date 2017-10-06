<?php
namespace App\Pvm\Behavior;

use App\Model\PvmToken;
use Formapro\Pvm\Behavior;
use Formapro\Pvm\Token;

class IdleBehavior implements Behavior
{
    /**
     * @param PvmToken $token
     *
     * {@inheritdoc}
     */
    public function execute(Token $token)
    {
    }
}
