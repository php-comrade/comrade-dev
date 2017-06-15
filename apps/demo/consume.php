<?php
use App\Infra\Swoole\Daemon;
use Symfony\Component\Process\PhpExecutableFinder;

require __DIR__.'/../jm/vendor/autoload.php';

$phpBin = (new PhpExecutableFinder)->find();
if (false === $phpBin) {
    throw new \LogicException('Php executable could not be found');
}


$daemon = new Daemon();
$daemon->addWorker(3, $phpBin, ['demo.php']);

$daemon->run();