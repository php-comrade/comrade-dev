<?php

require __DIR__ . '/vendor/autoload.php';

use Bunny\Channel;
use Bunny\Message;
use Thruway\ClientSession;
use Thruway\Peer\Client;
use Thruway\Transport\PawlTransportProvider;
use Bunny\Async\Client as BunnyClient;

$client = new Client("realm1");
$client->addTransportProvider(new PawlTransportProvider("ws://127.0.0.1:9090/"));

$client->on('open', function (ClientSession $session) {

//    $session->publish('events', 'Fuck!!!!');

    // 1) subscribe to a topic
    $onevent = function ($args) {
        echo "Event {$args[0]}\n";
    };
    $session->subscribe('com.myapp.hello', $onevent);

    // 2) publish an event
    $session->publish('com.myapp.hello', ['Hello, world from PHP!!!'], [], ["acknowledge" => true])->then(
        function () {
            echo "Publish Acknowledged!\n";
        },
        function ($error) {
            // publish failed
            echo "Publish Error {$error}\n";
        }
    );

    // 3) register a procedure for remoting
    $add2 = function ($args) {
        return $args[0] + $args[1];
    };
    $session->register('com.myapp.add2', $add2);

    // 4) call a remote procedure
    $session->call('com.myapp.add2', [2, 3])->then(
        function ($res) {
            echo "Result: {$res}\n";
        },
        function ($error) {
            echo "Call Error: {$error}\n";
        }
    );


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


$client->start();