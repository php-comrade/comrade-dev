<?php
namespace App\Queue;

use App\Topics;
use App\Infra\Error\Error;
use App\Infra\Error\ErrorStorage;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Result;
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
        try {
            $data = JSON::decode($psrMessage->getBody());

            if (false == is_array($data)) {
                throw new \LogicException('Data must be an array');
            }

            $error = new Error();
            set_values($error, $data);

            $this->errorStorage->insert($error);
        } catch (\Throwable $e) {
            return Result::reject(sprintf('%s: %s in %s line %s', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
        }

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
