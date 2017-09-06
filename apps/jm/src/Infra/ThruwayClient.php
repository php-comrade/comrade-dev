<?php
namespace App\Infra;

use Voryx\ThruwayBundle\Client\ClientManager;

class ThruwayClient
{
    /**
     * @var ClientManager
     */
    private $client;

    public function __construct(ClientManager $client)
    {
        $this->client = $client;
    }

    public function publish(string $topic, $data): void
    {
        $this->client->publish($topic, [$data]);
    }
}
