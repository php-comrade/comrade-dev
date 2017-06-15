<?php
namespace App\Infra\Enqueue;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface;

class CheckMasterProcessExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

    public function onBeforeReceive(Context $context)
    {
        if (false == $mPid = getenv('MASTER_PROCESS_PID')) {
            throw new \LogicException('The extension rely on MASTER_PROCESS_PID env var set but it is not set.');
        }

        if(false == \swoole_process::kill($mPid,0)){
            $context->setExecutionInterrupted(true);

            $context->getLogger()->info('[CheckMasterProcessExtension] The master process exited. So do I');
        }
    }
}