<?php
namespace App\Infra\Enqueue;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface;

class CheckMasterProcessExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

    /**
     * @var
     */
    private $pidFil;

    /**
     * @param $pidFile
     */
    public function __construct($pidFile)
    {
        $this->pidFil = $pidFile;
    }

    public function onBeforeReceive(Context $context)
    {
        $mPid = file_get_contents($this->pidFil);

        if(false == \swoole_process::kill($mPid,0)){
            $context->setExecutionInterrupted(true);

            $context->getLogger()->info('[CheckMasterProcessExtension] The master process existed. So do I');
        }
    }
}