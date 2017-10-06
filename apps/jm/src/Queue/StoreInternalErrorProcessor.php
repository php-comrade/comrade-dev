<?php
namespace App\Queue;

use App\Topics;
use App\Infra\Error\Error;
use App\Infra\Error\ErrorStorage;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Util\JSON;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use function Makasim\Values\set_values;

class StoreInternalErrorProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const PROCESSOR_NAME = 'store_internal_error';

    /**
     * @var ErrorStorage
     */
    private $errorStorage;

    /**
     * @param ErrorStorage $errorStorage
     */
    public function __construct(ErrorStorage $errorStorage)
    {
        $this->errorStorage = $errorStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $psrMessage, PsrContext $psrContext)
    {
        $data = JSON::decode($psrMessage->getBody());

        if (false == is_array($data)) {
            throw new \LogicException('Data must be an array');
        }

        $error = new Error();
        set_values($error, $data);

        $this->errorStorage->insert($error);

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [
            Topics::INTERNAL_ERROR => [
                'processorName' => self::PROCESSOR_NAME,
                'queueName' => 'store_internal_error',
            ]
        ];
    }
}
