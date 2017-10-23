<?php

use Comrade\Shared\Message\RunJob;
use Comrade\Shared\Model\JobAction;
use function Makasim\Values\register_cast_hooks;
use function Makasim\Values\register_object_hooks;
use Psr\Http\Message\ResponseInterface;

error_reporting(E_ALL);

require_once __DIR__.'/vendor/autoload.php';

$path = $_SERVER["SCRIPT_NAME"];
if ('/' === $path) {
    echo <<<HTML
<h3>Http Demo worker</h3>

<p>Available endpoints: </p>

<ul>
    <li>/demo_success_job</li>
</ul>
HTML;

    exit;
}

register_cast_hooks();
register_object_hooks();

$runner = new \Comrade\Client\ClientHttpRunner('http://jm');

if ('/demo_success_job' === $path) {
    $httpRequest = \GuzzleHttp\Psr7\ServerRequest::fromGlobals();

    $httpResponse = $runner->run($httpRequest, function(RunJob $runJob) {
        do_something_important(rand(2, 6));

        return JobAction::COMPLETE;
    });

    send($httpResponse);
    exit;
}

echo 'Not Found';
http_send_status(404);

function send(ResponseInterface $response) {
    // status
    header(sprintf('HTTP/%s %s %s', $response->getProtocolVersion(), $response->getStatusCode(), $response->getReasonPhrase()), true, $response->getStatusCode());

    foreach ($response->getHeaders() as $name => $values) {
        foreach ($values as $value) {
            header(sprintf('%s: %s', $name, $value), false, $response->getStatusCode());
        }
    }

    echo $response->getBody()->getContents();
}


function do_something_important($timeout)
{
    $limit = microtime(true) + $timeout;

    $memoryConsumed = false;

    while (microtime(true) < $limit) {

        if ($memoryConsumed) {
            foreach (range(1000000, 5000000) as $index) {
                $arr[] = $index;
            }

            $memoryConsumed = true;
        }

        usleep(10000);
    }
}