<?php
namespace App\Infra\Swoole;

class Daemon
{
    /**
     * @var int[]
     */
    private $pipes = [];

    /**
     * @var \swoole_process[]
     */
    private $workers = [];

    /**
     * @var bool
     */
    private $exiting = false;

    /**
     * @var array
     */
    private $workersData = [];

    public function addWorker(int $quantity, string $execFile, array $arguments):void
    {
        $data = ['quantity' => $quantity, 'execFile' => $execFile, 'arguments' => $arguments];

        $this->workersData[md5(serialize($data))] = $data;
    }

    public function run():void
    {
        putenv('MASTER_PROCESS_PID=' . getmypid());

        \swoole_process::signal(SIGCHLD, [$this, 'rebootProcess']);
        \swoole_process::signal(SIGTERM, [$this, 'handleSignal']);
        \swoole_process::signal(SIGQUIT, [$this, 'handleSignal']);
        \swoole_process::signal(SIGINT, [$this, 'handleSignal']);
        \swoole_process::signal(SIGUSR1, [$this, 'handleSignal']);

        foreach ($this->workersData as $dataId => $workerData) {
            foreach (range(1, $workerData['quantity']) as $index) {
                $this->execProcess($dataId, $workerData);
            }
        }
    }

    public function onReceive(int $pipe):void
    {
        if (array_key_exists($pipe, $this->pipes)) {
            $worker = $this->pipes[$pipe];

            echo str_replace(PHP_EOL, PHP_EOL . $worker->pid . " | ", $worker->read());
        }
    }

    public function handleSignal(int $signal):void
    {
        echo "master | signal caught\n";

        switch ($signal) {
            case SIGUSR1:   // reload
                echo "master | reloading workers\n";
                foreach (array_keys($this->workers) as $cPid) {
                    \swoole_process::kill($cPid, SIGTERM);
                }

                break;
            case SIGTERM:  // 15 : supervisor default stop
            case SIGQUIT:  // 3  : kill -s QUIT
            case SIGINT:   // 2  : ctrl+c
                $this->exiting = true;

                foreach (array_keys($this->workers) as $cPid) {
                    \swoole_process::kill($cPid, SIGTERM);
                }

                while ($ret = \swoole_process::wait()) {
                    echo "master | child process {$ret['pid']} exited\n";

                    $this->removeProcess($ret['pid']);

                    if (empty($this->workers)) {
                        echo "master | exited\n";

                        exit;
                    }
                }

                break;
            default:
                break;
        }
    }

    public function execProcess(string $dataId, array $workerData):\swoole_process
    {
        $process = new \swoole_process(function (\swoole_process $process) use ($workerData) {
            $process->exec($workerData['execFile'], $workerData['arguments']);
        }, true, true);

        $process->dataId = $dataId;
        $process->start();

        $this->pipes[$process->pipe] = $process;
        $this->workers[$process->pid] = $process;

        swoole_event_add($process->pipe, [$this, 'onReceive']);

        return $process;
    }

    public function rebootProcess():void
    {
        // represents the child process has closed, recycle it
        if ($ret = \swoole_process::wait()) {
            echo "master | Child process: {$ret['pid']} exited with status {$ret['code']}\n";

            if (false == $this->exiting) {
                $oldProcess = $this->workers[$ret['pid']];

                $this->removeProcess($ret['pid']);

                $workerData = $this->workersData[$oldProcess->dataId];
                $newProcess = $this->execProcess($oldProcess->dataId, $workerData);

                echo "master | Reboot process: {$ret['pid']} => {$newProcess->pid} Done\n";
            }
        }
    }

    public function removeProcess(int $pid):void
    {
        $worker = $this->workers[$pid];

        swoole_event_del($worker->pipe);
        unset($this->pipes[$worker->pipe]);
        unset($this->workers[$worker->pid]);
    }
}
