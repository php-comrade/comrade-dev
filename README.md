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

## Installation

The best way to run Comrade in production is to use pre-build Docker containers. 

```yaml
version: '3'

services:
  jm:
    image: 'formapro/comrade:latest'
    ports: ['80:80']
    depends_on: ['rabbitmq', 'mongo']
    environment:
      - ENQUEUE_DSN=amqp://guest:guest@rabbitmq:5672/comrade
      - MONGO_DSN=mongodb://mongo:27017

  jmw:
    image: 'formapro/comrade:latest'
    entrypoint: "php bin/daemon.php"
    depends_on: ['rabbitmq', 'mongo']
    environment:
      - ENQUEUE_DSN=amqp://guest:guest@rabbitmq:5672/comrade
      - MONGO_DSN=mongodb://mongo:27017
      - WAMP_DSN=ws://jms:9090
      - WAMP_REALM=realm1
      - WAMP_SERVER_HOST=0.0.0.0
      - WAMP_SERVER_PORT=9090
    ports:
      - "9090:9090"

  rabbitmq:
    image: 'enqueue/rabbitmq:latest'
    environment:
      - RABBITMQ_DEFAULT_USER=guest
      - RABBITMQ_DEFAULT_PASS=guest
      - RABBITMQ_DEFAULT_VHOST=comrade

  mongo:
    image: 'mongo:3'
```

If you'd like to to run Comrade from source codes do next:

```bash
$ git clone git@github.com:php-comrade/comrade-dev.git;
$ (cd comrade-dev/apps/jm; composer install)
$ (cd comrade-dev/apps/ui; npm install)
$ echo '127.0.0.1 jm.loc' >> /etc/hosts
$ echo '127.0.0.1 ui.jm.loc' >> /etc/hosts
$ bin/dup
```

The Comrade service will be available at `jm.loc` host and its UI at `ui.jm.loc`.

## Online Demo

The UI demo available at http://demo.comrade.forma-pro.com the Comrade service at http://demo.comrade.forma-pro.com:81

## Developed by Forma-Pro

Forma-Pro is a full stack development company which interests also spread to open source development. 
Being a team of strong professionals we have an aim an ability to help community by developing cutting edge solutions in the areas of e-commerce, docker & microservice oriented architecture where we have accumulated a huge many-years experience. 
Our main specialization is Symfony framework based solution, but we are always looking to the technologies that allow us to do our job the best way. We are committed to creating solutions that revolutionize the way how things are developed in aspects of architecture & scalability.

If you have any questions and inquires about our open source development, this product particularly or any other matter feel free to contact at opensource@forma-pro.com

## License

It is released under the [MIT License](LICENSE).   