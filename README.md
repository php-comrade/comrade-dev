# Comrade

Comrade is job scheduler and manager service.

## Features

* Exclusive jobs
* Dependent jobs
* Sub jobs execution.
* Cron like triggers
* Grace period and retry polices
* Queue and HTTP runners
* Polyglot API. Supports HTTP and MQ API.
* JSON Schemas for everything,
* Real-time UI is provided, 
* A job could be executed on any programming language. 
* Standalone micro service,
* Containerized

## Online Demo

The Comrade UI is available at [http://demo.comrade.forma-pro.com](http://demo.comrade.forma-pro.com/settings/base-url?apiBaseUrl=http://demo.comrade.forma-pro.com:81) and the service itself is at http://demo.comrade.forma-pro.com:81.


## Installation

The best way to run Comrade in production is to use pre-build Docker containers.

1. Create an empty directory
2. Inside, create an `.env` file with content:

    ```
    APP_ENV=prod
    APP_DEBUG=0
    APP_SECRET=45402ed3d65de420f63e1546c322968c
    ENQUEUE_DSN=amqp+bunny://comrade:comrade123@rabbitmq:5672/comrade?receive_method=basic_consume&lazy=1&qos_prefetch_count=3&persisted=1&connection_timeout&connection_timeout=20
    MONGO_DSN=mongodb://mongo:27017/comrade
    WAMP_DSN=ws://jms:9090
    WAMP_REALM=realm1
    WAMP_SERVER_HOST=0.0.0.0
    WAMP_SERVER_PORT=9090
    ```

3. Create `docker-compose.yml` file with next content:
 
    ```yaml
    version: '3'
    
    services:
      jm:
        image: 'formapro/comrade:latest'
        ports:
          - "81:80"
        depends_on:
          - 'rabbitmq'
          - 'mongo'
        env_file: '/.env'
    
      jmc:
        image: 'formapro/comrade:latest'
        entrypoint: 'php bin/console enqueue:consume --setup-broker --receive-timeout=10000 --memory-limit=100 -vvv'
        depends_on:
          - 'rabbitmq'
          - 'mongo'
        env_file: '/.env'
    
      jmq:
        image: 'formapro/comrade:latest'
        entrypoint: 'php bin/console quartz:scheduler -vvv'
        depends_on:
          - 'rabbitmq'
          - 'mongo'
        env_file: '/.env'
        deploy:
          mode: 'global'
          
      jmw:
        image: 'formapro/comrade:latest'
        entrypoint: 'php bin/wamp_server.php'
        depends_on:
          - 'rabbitmq'
          - 'mongo'
        env_file: '/.env'
        ports:
          - '9090:9090'
    
      ui:
        image: 'formapro/comrade-ui:latest'
        ports:
          - "80:80"
    
      rabbitmq:
        image: 'rabbitmq:latest'
        environment:
          - 'RABBITMQ_DEFAULT_USER=comrade'
          - 'RABBITMQ_DEFAULT_PASS=comrade123'
          - 'RABBITMQ_DEFAULT_VHOST=comrade'
    
      mongo: { image: 'mongo:3' }
    ```
    
4. Run `docker-compose up`. Now you have the comrade server available at `localhost:81`, UI at `localhost:80`, websoket server at `localhost:9090`.

If you'd like to build and run Comrade from source code, do next:

```bash
$ git clone git@github.com:php-comrade/comrade-dev.git;
$ (cd comrade-dev/apps/jm; composer install);
$ (cd comrade-dev/apps/ui; npm install);
$ echo '127.0.0.1 jm.loc' >> /etc/hosts
$ echo '127.0.0.1 ui.jm.loc' >> /etc/hosts
$ bin/dup
```

The Comrade service will be available at `jm.loc` host and its UI at `ui.jm.loc`.

## PHP client.

The Comrade exposes for various things like create job, add trigger, query jobs operations. They are available through HTTP as well as message queue. Same format.
It is possible to create job from any programming language as well as run it later. For PHP we provide a simple client

```
composer require comrade/client:*@dev 
```

With the client you can create a job by doing:

```php
<?php
use Comrade\Shared\Model\JobTemplate;
use Comrade\Shared\Model\QueueRunner;
use Comrade\Shared\Model\GracePeriodPolicy;
use Comrade\Shared\Message\CreateJob;
use Comrade\Shared\Model\CronTrigger;
use Enqueue\Util\UUID;
use Enqueue\Util\JSON;
use function Enqueue\dsn_to_context;
use function Makasim\Values\register_cast_hooks;
use function Makasim\Values\register_object_hooks;
use Interop\Queue\PsrContext;

require_once __DIR__.'/vendor/autoload.php';

register_cast_hooks();
register_object_hooks();

/** @var PsrContext $queueContext */
$context = dsn_to_context(getenv('ENQUEUE_DSN'));

$template = JobTemplate::create();
$template->setName('demo_success_job');
$template->setTemplateId(Uuid::generate());
$template->setRunner(QueueRunner::createFor('demo_success_job'));

$policy = GracePeriodPolicy::create();
$policy->setPeriod(20);
$template->setGracePeriodPolicy($policy);

$trigger = CronTrigger::create();
$trigger->setTemplateId($template->getTemplateId());
$trigger->setStartAt(new \DateTime('now'));
$trigger->setMisfireInstruction(CronTrigger::MISFIRE_INSTRUCTION_FIRE_ONCE_NOW);
$trigger->setExpression('*/5 * * * *');

$createJob = CreateJob::createFor($template);
$createJob->addTrigger($trigger);

$queue = $context->createQueue('comrade_create_job');
$message = $context->createMessage(JSON::encode($createJob));
$context->createProducer()->send($queue, $message);
```

Job has `QueueRunner`. It means that when it is time to run the job it is sent to `demo_success_job` queue. 
Job has `GracePeriodPolicy`. It means that if the job is not finished within 20 seconds it is marked as failed.
Job has `CronTrigger`. It means the job should be run every five minutes.

There is possibility to set other polices, triggers or another runner.
The job worker could look like this:

```php
<?php

use Comrade\Shared\Message\RunJob;
use Comrade\Shared\Model\JobAction;
use Comrade\Client\ClientQueueRunner;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use function Enqueue\dsn_to_context;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Result;
use function Makasim\Values\register_cast_hooks;
use function Makasim\Values\register_object_hooks;

register_cast_hooks();
register_object_hooks();

/** @var PsrContext $c */
$c = dsn_to_context(getenv('ENQUEUE_DSN'));

$runner = new ClientQueueRunner($c);

$queueConsumer = new QueueConsumer($c);

$queueConsumer->bind('demo_success_job', function(PsrMessage $message) use ($runner) {
    $runner->run($message, function(RunJob $runJob) {
        do_something_important(rand(200, 1000));

        return JobAction::COMPLETE;
    });

    return Result::ACK;
});

$queueConsumer->consume();
```

## Developed by Forma-Pro

Forma-Pro is a full stack development company which interests also spread to open source development. 
Being a team of strong professionals we have an aim an ability to help community by developing cutting edge solutions in the areas of e-commerce, docker & microservice oriented architecture where we have accumulated a huge many-years experience. 
Our main specialization is Symfony framework based solution, but we are always looking to the technologies that allow us to do our job the best way. We are committed to creating solutions that revolutionize the way how things are developed in aspects of architecture & scalability.

If you have any questions and inquires about our open source development, this product particularly or any other matter feel free to contact at opensource@forma-pro.com

## License

It is released under the [MIT License](LICENSE).   
