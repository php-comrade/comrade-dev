<?php
namespace App\Queue;

use App\Topics;
use App\Infra\Error\Error;
use App\Infra\Error\ErrorStorage;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Util\JSON;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use function Makasim\Values\set_values;

class StoreInternalErrorProcessor implements Processor, TopicSubscriberInterface
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
    public function process(Message $Message, Context $Context)
    {
        try {
            $data = JSON::decode($Message->getBody());

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

    public static function getSubscribedTopics(): array
    {
        return [
            Topics::INTERNAL_ERROR => [
                'processor' => self::PROCESSOR_NAME,
                'queue' => 'store_internal_error',
            ]
        ];
    }
}
