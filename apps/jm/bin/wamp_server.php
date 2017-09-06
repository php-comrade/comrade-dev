<?php

ini_set("xdebug.max_nesting_level","200");

require __DIR__.'/../vendor/autoload.php';

use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

$router = new Router();

$wampHost = getenv('WAMP_SERVER_HOST') ?: '127.0.0.1';
$wampPort = getenv('WAMP_SERVER_PORT') ?: '9090';

$transportProvider = new RatchetTransportProvider($wampHost, $wampPort);

$router->addTransportProvider($transportProvider);

$signalHandler = function($signal) use($router) {
    echo 'Exiting '.PHP_EOL;

    $router->getLoop()->stop();
};

pcntl_signal(SIGTERM, $signalHandler);
pcntl_signal(SIGINT, $signalHandler);
pcntl_signal(SIGQUIT, $signalHandler);

$router->getLoop()->addPeriodicTimer(1, 'pcntl_signal_dispatch');

$router->start();