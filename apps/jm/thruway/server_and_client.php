<?php

ini_set("xdebug.max_nesting_level","200");

require __DIR__.'/vendor/autoload.php';

use Thruway\Event\ConnectionOpenEvent;
use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;
use Bunny\Channel;
use Bunny\Message;
use Thruway\ClientSession;
use Thruway\Peer\Client;
use Thruway\Transport\PawlTransportProvider;
use Bunny\Async\Client as BunnyClient;

$router = new Router();

$transportProvider = new RatchetTransportProvider("0.0.0.0", 9090);

$router->addTransportProvider($transportProvider);

$router->start();