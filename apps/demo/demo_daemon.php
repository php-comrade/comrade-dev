<?php

use Comrade\Demo\Daemon;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\ProcessBuilder;

require __DIR__.'/vendor/autoload.php';

$phpBin = (new PhpExecutableFinder)->find();
if (false === $phpBin) {
    throw new \LogicException('Php executable could not be found');
}

$demoNumber = getenv('COMRADE_DEMO_NUMBER') ?: 3;

$daemon = new Daemon();

$builder = new ProcessBuilder([$phpBin, 'queue_demo.php']);
$builder->setPrefix('exec');
$builder->setEnv('MASTER_PROCESS_PID', getmypid());
$daemon->addWorker('queue_demo', $demoNumber, $builder);

$builder = new ProcessBuilder([$phpBin, '-S', '0.0.0.0:80', 'http_demo.php']);
$builder->setPrefix('exec');
$builder->setEnv('MASTER_PROCESS_PID', getmypid());
$daemon->addWorker('http_demo', 1, $builder);

$daemon->start();
