<?php
use Symfony\Component\Filesystem\LockHandler;
use Symfony\Component\Process\PhpExecutableFinder;

require_once __DIR__.'/../vendor/autoload.php';

$pipes = [];
$workers = [];
$worker_num = 3;
$exiting = false;

$lock = new LockHandler('workers', __DIR__.'/../var');

if (false === $lock->lock(false)) {
    throw new \LogicException('There is another workers.php daemon process is working.');
}

file_put_contents(__DIR__.'/../var/workers.pid', getmypid());

//swoole_process::daemon(0, 1);
function onReceive($pipe) {
    global $pipes;

    if (array_key_exists($pipe, $pipes)) {
        $worker = $pipes[$pipe];

        echo str_replace(PHP_EOL, PHP_EOL . $worker->pid . " | ", $worker->read());
    }
}
// loop creation process
for($i = 0; $i < $worker_num; $i++) {
    createProcess();
}

swoole_process::signal(SIGCHLD, 'rebootProcess');
swoole_process::signal(SIGTERM, 'handleSignal');
swoole_process::signal(SIGQUIT, 'handleSignal');
swoole_process::signal(SIGINT, 'handleSignal');
swoole_process::signal(SIGUSR1, 'handleSignal');

function handleSignal($signal) {
    echo "master | signal caught\n";

    global $workers, $exiting;

    switch ($signal) {
        case SIGUSR1:   // reload
            echo "master | reloading workers\n";
            foreach (array_keys($workers) as $cPid) {
                \swoole_process::kill($cPid, SIGTERM);
            }

            break;
        case SIGTERM:  // 15 : supervisor default stop
        case SIGQUIT:  // 3  : kill -s QUIT
        case SIGINT:   // 2  : ctrl+c
            $exiting = true;

            foreach (array_keys($workers) as $cPid) {
                \swoole_process::kill($cPid, SIGTERM);
            }

            while ($ret = swoole_process::wait()) {
                echo "master | child process {$ret['pid']} exited\n";

                removeProcess($ret['pid']);

                if (empty($workers)) {
                    echo "master | exited\n";

                    exit;
                }
            }

            break;
        default:
            break;
    }
}

function rebootProcess()
{
    global $exiting;

    // represents the child process has closed, recycle it
    if ($ret = swoole_process::wait()) {
        echo "master | Child process: {$ret['pid']} exited with status {$ret['code']}\n";

        if (false == $exiting) {
            removeProcess($ret['pid']);
            $newProcess = createProcess();

            echo "master | Reboot process: {$ret['pid']} => {$newProcess->pid} Done\n";
        }
    }
}

function createProcess():\swoole_process
{
    global $pipes, $workers;

    $process = new \swoole_process(function(\swoole_process $process) {
        $phpBin = (new PhpExecutableFinder)->find();
        if (false !== $phpBin) {
            $process->exec($phpBin, [__DIR__ . '/console', 'enqueue:consume', '--setup-broker', '-vvv']);
        } else {
            throw new \LogicException('Php executable could not be found');
        }
    }, true, true);

    $process->start();

    $pipes[$process->pipe] = $process;
    $workers[$process->pid] = $process;

    swoole_event_add($process->pipe, 'onReceive');

    return $process;
}

function removeProcess($pid)
{
    global $workers, $pipes;

    $worker = $workers[$pid];

    swoole_event_del($worker->pipe);
    unset($pipes[$worker->pipe]);
    unset($workers[$worker->pid]);
}
