<?php
use App\Infra\Enqueue\ConsumeDaemon;
use Symfony\Component\Process\ProcessBuilder;

require __DIR__.'/../vendor/autoload.php';

$workerBuilder = new ProcessBuilder(['bin/console', 'enqueue:consume', '--setup-broker', '-vvv']);
$workerBuilder->setPrefix('php');
$workerBuilder->setWorkingDirectory(realpath(__DIR__.'/..'));

$daemon = new ConsumeDaemon($workerBuilder);
$daemon->start(1);