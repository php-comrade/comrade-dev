<?php
use Symfony\Component\Filesystem\LockHandler;
use Symfony\Component\Process\PhpExecutableFinder;

require_once __DIR__.'/../vendor/autoload.php';

$phpBin = (new PhpExecutableFinder)->find();
if (false === $phpBin) {
    throw new \LogicException('Php executable could not be found');
}

$daemon = new \App\Infra\Swoole\Daemon();
$daemon->addWorker(1, $phpBin, [__DIR__ . '/console', 'quartz:scheduler', '-vvv']);
$daemon->addWorker(3, $phpBin, [__DIR__ . '/console', 'enqueue:consume', '--setup-broker', '-vvv']);
$daemon->run();
