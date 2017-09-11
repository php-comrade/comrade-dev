<?php
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\ProcessBuilder;

require_once __DIR__.'/../vendor/autoload.php';

$phpBin = (new PhpExecutableFinder)->find();
if (false === $phpBin) {
    throw new \LogicException('Php executable could not be found');
}

$daemon = new \App\Infra\Symfony\Daemon();

$defaultConsumerNumber = false !== getenv('COMRADE_DEFAULT_CONSUMER_NUMBER') ? getenv('COMRADE_DEFAULT_CONSUMER_NUMBER') : 2;
$quartzConsumerNumber = false !== getenv('COMRADE_QUARTZ_CONSUMER_NUMBER') ? getenv('COMRADE_QUARTZ_CONSUMER_NUMBER') : 2;
$runQuartz = false !== getenv('COMRADE_RUN_QUARTZ') ? getenv('COMRADE_RUN_QUARTZ') : true;
$runWamp = false !== getenv('COMRADE_RUN_WAMP') ? getenv('COMRADE_RUN_WAMP') : true;

if ($defaultConsumerNumber) {
    $builder = new ProcessBuilder([$phpBin, 'bin/console', 'enqueue:consume', '--setup-broker', '--time-limit=+5 minutes', '-vvv']);
    $builder->setPrefix('exec');
    $builder->setWorkingDirectory(realpath(__DIR__ . '/..'));
    $builder->setEnv('MASTER_PROCESS_PID', getmypid());
    $daemon->addWorker('cnsmr', $defaultConsumerNumber, $builder);
}

if ($quartzConsumerNumber) {
    $builder = new ProcessBuilder([$phpBin, 'bin/console', 'enqueue:consume', 'quartz_job_run_shell', 'quartz_rpc', '--setup-broker', '--time-limit=+5 minutes',  '-vvv']);
    $builder->setPrefix('exec');
    $builder->setWorkingDirectory(realpath(__DIR__.'/..'));
    $builder->setEnv('MASTER_PROCESS_PID', getmypid());
    $daemon->addWorker('qvrtz-cnsmr', $quartzConsumerNumber, $builder);
}

if ($runQuartz) {
    $builder = new ProcessBuilder([$phpBin, 'bin/console', 'quartz:scheduler', '-vvv']);
    $builder->setPrefix('exec');
    $builder->setWorkingDirectory(realpath(__DIR__ . '/..'));
    $builder->setEnv('MASTER_PROCESS_PID', getmypid());
    $daemon->addWorker('schdlr', 1, $builder);
}

if ($runWamp) {
    $builder = new ProcessBuilder([$phpBin, 'bin/wamp_server.php']);
    $builder->setPrefix('exec');
    $builder->setWorkingDirectory(realpath(__DIR__ . '/..'));
    $builder->setEnv('MASTER_PROCESS_PID', getmypid());
    $daemon->addWorker('wamp', 1, $builder);
}

$daemon->start();
