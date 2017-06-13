<?php
namespace App\Infra\Enqueue;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface;

class RejectMessageOnExceptionExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

    public function onInterrupted(Context $context)
    {
        if (false == $exception = $context->getException()) {
            return;
        }

        $context->getPsrConsumer()->reject($context->getPsrMessage());

        $context->getLogger()->error('[RejectMessageOnExceptionExtension] The message has been rejected');
    }
}
