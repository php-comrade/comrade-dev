<?php

use App\Infra\Symfony\Daemon;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\ProcessBuilder;

require __DIR__.'/../jm/vendor/autoload.php';

$phpBin = (new PhpExecutableFinder)->find();
if (false === $phpBin) {
    throw new \LogicException('Php executable could not be found');
}

$daemon = new Daemon();

$builder = new ProcessBuilder([$phpBin, 'demo.php']);
$builder->setPrefix('exec');
$builder->setEnv('MASTER_PROCESS_PID', getmypid());
$daemon->addWorker('demo', 3, $builder);

$daemon->start();