<?php
namespace App\Infra\Error;

use App\Queue\StoreInternalErrorProcessor;
use App\Topics;
use App\Infra\Uuid;
use Enqueue\Client\Config;
use Enqueue\Client\ProducerInterface;
use Enqueue\Consumption\Context;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\Result;
use Interop\Queue\Exception;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ErrorCollector implements EventSubscriberInterface, ExtensionInterface
{
    use EmptyExtensionTrait;

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @param ProducerInterface $producer
     */
    public function __construct(ProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    public function onHttpException(GetResponseForExceptionEvent $event):void
    {
        $request = $event->getRequest();
        $exception = $event->getException();

        $error = $this->createNewError();
        $error->setValue('request', $this->convertRequest($request, $event->getRequestType()));
        $error->setValue('error', $this->convertThrowable($exception));

        if ($response = $event->getResponse()) {
            $error->setValue('response', $this->convertResponse($response));
        }

        try {
            $this->producer->sendEvent(Topics::INTERNAL_ERROR, $error);
        } catch (Exception $e) {}
    }

    public function onCliException(ConsoleErrorEvent $event):void
    {
        $error = $this->createNewError();
        $error->setValue('error', $this->convertThrowable($event->getError()));
        $error->setValue('cli.argv', array_key_exists('argv', $_SERVER) ? $_SERVER['argv'] : []);
        $error->setValue('cli.command', implode(' ', $error->getValue('cli.argv')));

        try {
            $this->producer->sendEvent(Topics::INTERNAL_ERROR, $error);
        } catch (Exception $e) {}
    }

    public function onPostReceived(Context $context)
    {
        if (PsrProcessor::REJECT !== (string) $context->getResult()) {
            return;
        }
        if (StoreInternalErrorProcessor::PROCESSOR_NAME == $context->getPsrMessage()->getProperty(Config::PARAMETER_PROCESSOR_NAME)) {
            // do not try to store rejections happened while trying to store an error. it must end up being an endless cycle.
            return;
        }

        $error = $this->createNewError();
        $error->setValue('queue.message', $this->convertQueueMessage($context->getPsrMessage()));

        $result = $context->getResult();
        $error->setValue('queue.result.status', (string) $result);
        $error->setValue('queue.result.reason', $result instanceof Result ? $result->getReason() : '');

        try {
            $this->producer->sendEvent(Topics::INTERNAL_ERROR, $error);
        } catch (Exception $e) {}
    }

    public function onInterrupted(Context $context)
    {
        if (false == $context->getException()) {
            return;
        }

        if ($context->getPsrMessage() && StoreInternalErrorProcessor::PROCESSOR_NAME == $context->getPsrMessage()->getProperty(Config::PARAMETER_PROCESSOR_NAME)) {
            // do not try to store errors happened while trying to store another error. it must end up being an endless cycle.
            return;
        }

        $error = $this->createNewError();
        $error->setValue('error', $this->convertThrowable($context->getException()));

        if ($context->getPsrMessage()) {
            $error->setValue('queue.message', $this->convertQueueMessage($context->getPsrMessage()));
        }
        if ($result = $context->getResult()) {
            $error->setValue('queue.result.status', (string) $result);
            $error->setValue('queue.result.reason', $result instanceof Result ? $result->getReason() : '');
        }

        try {
            $this->producer->sendEvent(Topics::INTERNAL_ERROR, $error);
        } catch (Exception $e) {}
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents():array
    {
        return [
            KernelEvents::EXCEPTION => 'onHttpException',
            ConsoleEvents::ERROR => 'onCliException'
        ];
    }

    private function convertRequest(Request $request, $requestType):array
    {
        return [
            'type' => $requestType,
            'method' => $request->getMethod(),
            'requestUri' => $request->getRequestUri(),
            'serverProtocol' => $request->server->get('SERVER_PROTOCOL'),
            'attributes' => $request->attributes->all(),
            'raw' => (string) $request,
        ];
    }

    private function convertResponse(Response $response):array
    {
        return [
            'response.raw' => (string) $response,
            'response.version' => $response->getProtocolVersion(),
            'response.statusCode' => $response->getStatusCode(),
        ];
    }
    
    private function convertThrowable(\Throwable $error):array
    {
        $rawError = [
            'raw' => (string) $error,
            'message' => $error->getMessage(),
            'code' => $error->getCode(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => $error->getTraceAsString(),
        ];

        if ($error->getPrevious()) {
            $rawError['previous'] = $this->convertThrowable($error->getPrevious());
        }

        return $rawError;
    }

    private function convertQueueMessage(PsrMessage $message): array
    {
        return [
            // key might contain a dot and mongodb complains on it"
            'properties' => json_encode($message->getProperties()),
            'headers' => json_encode($message->getHeaders()),
            'body' => str_pad($message->getBody(), 1000, ' [...]'),
            'isRedelivered' => $message->isRedelivered(),
            'topicName' => $message->getProperty(Config::PARAMETER_TOPIC_NAME),
            'processorName' => $message->getProperty(Config::PARAMETER_PROCESSOR_NAME),
            'processorQueueName' => $message->getProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME),
            'commandName' => $message->getProperty(Config::PARAMETER_COMMAND_NAME),
        ];
    }

    private function createNewError():Error
    {
        $nowMicrotime = (int) (new \DateTime('now'))->format('Uu');

        $error = new Error();
        $error->setValue('createdAt', $nowMicrotime);
        $error->setValue('id', Uuid::generate());

        return $error;
    }
}