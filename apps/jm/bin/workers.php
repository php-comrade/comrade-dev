<?php
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\ProcessBuilder;

require_once __DIR__.'/../vendor/autoload.php';

$phpBin = (new PhpExecutableFinder)->find();
if (false === $phpBin) {
    throw new \LogicException('Php executable could not be found');
}

$daemon = new \App\Infra\Symfony\Daemon();

$builder = new ProcessBuilder([$phpBin, 'bin/console', 'enqueue:consume', '--setup-broker', '-vvv']);
$builder->setPrefix('exec');
$builder->setWorkingDirectory(realpath(__DIR__.'/..'));
$builder->setEnv('MASTER_PROCESS_PID', getmypid());
$daemon->addWorker('cnsmr', 3, $builder);

$builder = new ProcessBuilder([$phpBin, 'bin/console', 'quartz:scheduler', '-vvv']);
$builder->setPrefix('exec');
$builder->setWorkingDirectory(realpath(__DIR__.'/..'));
$builder->setEnv('MASTER_PROCESS_PID', getmypid());
$daemon->addWorker('schdlr', 1, $builder);

$daemon->start();
