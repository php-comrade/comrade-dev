<?php
namespace App\Service;

use MongoDB\Client;

class BuildMongoIndexesService
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $dbName;

    /**
     * @param Client $client
     * @param string $dbName
     */
    public function __construct(Client $client, string $dbName)
    {
        $this->client = $client;
        $this->dbName = $dbName;
    }

    public function build()
    {
        $db = $this->client->selectDatabase($this->dbName);

        $db->selectCollection('job_template')->createIndex([
            'templateId' => 1,
        ], ['unique' => true]);

        $db->selectCollection('job')->createIndex([
            'templateId' => 1,
        ]);

        $db->selectCollection('job')->createIndex([
            'processTemplateId' => 1,
        ]);

        $db->selectCollection('job')->createIndex([
            'id' => 1,
        ], ['unique' => true]);

        $db->selectCollection('job')->createIndex([
            'processId' => 1,
        ], ['unique' => true]);

        $db->selectCollection('pvm_process')->createIndex([
            'templateId' => 1,
        ]);

        $db->selectCollection('pvm_process_execution')->createIndex([
            'templateId' => 1,
        ]);

        $db->selectCollection('pvm_process_execution')->createIndex([
            'id' => 1,
        ], ['unique' => true]);

        $db->selectCollection('exclusive_job')->createIndex([
            'name' => 1,
        ], ['unique' => true]);
    }
}