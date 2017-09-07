<?php
namespace App\Infra;

use React\Promise\Deferred;
use Thruway\ClientSession;
use Thruway\Transport\TransportInterface;
use Thruway\Peer\Client;
use Thruway\Transport\PawlTransportProvider;

class ThruwayClient
{
    /**
     * @var string
     */
    private $serverUrl;

    /**
     * @var string
     */
    private $realm;

    public function __construct(string $serverUrl, string $realm)
    {
        $this->serverUrl = $serverUrl;
        $this->realm = $realm;
    }

    public function publish(string $topicName, ...$arguments): void
    {
        // work around react dns issue on docker env.
        // https://github.com/voryx/Thruway/pull/203
        // https://github.com/reactphp/dns/issues/10#issuecomment-285042906
        $host = parse_url($this->serverUrl, PHP_URL_HOST);
        $port = parse_url($this->serverUrl, PHP_URL_PORT);
        $schema = parse_url($this->serverUrl, PHP_URL_SCHEME);

        $ip = gethostbyname($host);

        $serverUrl = "$schema://$ip:$port/";

        // the rest of this method is mostly copy-pasted from
        // https://github.com/voryx/ThruwayBundle/blob/master/src/Voryx/ThruwayBundle/Client/ClientManager.php

        $client = new Client($this->realm);
        $client->setAttemptRetry(false);
        $client->addTransportProvider(new PawlTransportProvider($serverUrl));

        $deferrer = new Deferred();

        $argumentsKw = [];

        $options = (object) [];
        $options->acknowledge = true;

        $client->on("open", function (ClientSession $session, TransportInterface $transport) use ($deferrer, $topicName, $arguments, $argumentsKw, $options) {
            $session
                ->publish($topicName, $arguments, $argumentsKw, $options)
                ->then(
                    function () use ($deferrer, $transport) {
                        $transport->close();
                        $deferrer->resolve();
                    },
                    function() {
                        throw new \LogicException('An error happend while publishing to wamp server');
                    }
                    );
        });

        $client->on("error", function ($error) use ($topicName) {
            throw new \LogicException('An error happend while publishing to wamp server', var_export($error, true));
        });

        $client->start();
    }
}
