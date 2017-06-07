<?php
use App\Infra\Enqueue\ConsumeDaemon;
use Symfony\Component\Process\ProcessBuilder;

require __DIR__.'/../jm/vendor/autoload.php';

$workerBuilder = new ProcessBuilder(['demo.php']);
$workerBuilder->setPrefix('php');
$workerBuilder->setWorkingDirectory(realpath(__DIR__));

$daemon = new ConsumeDaemon($workerBuilder);
$daemon->start(3);