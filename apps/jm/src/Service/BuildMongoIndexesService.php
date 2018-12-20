<?php
namespace App\Service;

use Makasim\Yadm\CollectionFactory;
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
    private $mongoDsn;

    /**
     * @param Client $client
     * @param string $mongoDsn
     */
    public function __construct(Client $client, string $mongoDsn)
    {
        $this->client = $client;
        $this->mongoDsn = $mongoDsn;
    }

    public function build()
    {
        $collectionFactory = new CollectionFactory($this->client, $this->mongoDsn);

        $collectionFactory->create('job_template')->createIndex([
            'templateId' => 1,
        ], ['unique' => true]);

        $collectionFactory->create('job')->createIndex([
            'templateId' => 1,
        ]);

        $collectionFactory->create('job')->createIndex([
            'processTemplateId' => 1,
        ]);

        $collectionFactory->create('job')->createIndex([
            'id' => 1,
        ], ['unique' => true]);

        $collectionFactory->create('job')->createIndex([
            'processId' => 1,
        ]);

        $collectionFactory->create('pvm_process')->createIndex([
            'templateId' => 1,
        ]);

        $collectionFactory->create('pvm_process_execution')->createIndex([
            'templateId' => 1,
        ]);

        $collectionFactory->create('pvm_process_execution')->createIndex([
            'id' => 1,
        ], ['unique' => true]);

        $collectionFactory->create('exclusive_job')->createIndex([
            'name' => 1,
        ], ['unique' => true]);
    }
}