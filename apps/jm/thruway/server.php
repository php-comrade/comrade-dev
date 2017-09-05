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

$client = null;
$router = new Router();
$router->getEventDispatcher()->addListener('connection_open', function(ConnectionOpenEvent $event) use (&$client){
    if ($client) {
        return;
    }

    $client = new Client("realm1", $event->session->getLoop());
    $client->addTransportProvider(new PawlTransportProvider("ws://127.0.0.1:9090/"));

    $client->on('open', function (ClientSession $session) {
        $options = ['host' => 'rabbitmq', 'port' => 5672, 'vhost' => 'jm', 'user' => 'guest', 'password' => 'guest'];
        (new BunnyClient($session->getLoop(), $options))->connect()->then(function (BunnyClient $client) {
            return $client->channel();
        })->then(function (Channel $channel) {
            return $channel->qos(0, 5)->then(function () use ($channel) {
                return $channel;
            });
        })->then(function (Channel $channel) use ($session) {
            $channel->consume(
                function (Message $message, Channel $channel, BunnyClient $client) use ($session) {
                    $session->publish('com.myapp.hello', [$message->content]);

                    $channel->ack($message);
                },
                'test_thruway_queue'
            );
        });
    });

    $client->start(false);
});

$transportProvider = new RatchetTransportProvider("0.0.0.0", 9090);

$router->addTransportProvider($transportProvider);

$router->start();