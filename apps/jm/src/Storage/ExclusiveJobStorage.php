<?php
namespace App\Storage;

use App\Model\ExclusiveJob;
use function Makasim\Yadm\get_object_id;
use Makasim\Yadm\Storage;

/**
 * @method ExclusiveJob|null create()
 * @method ExclusiveJob|null findOne(array $filter = [], array $options = [])
 * @method ExclusiveJob[]|\Traversable find(array $filter = [], array $options = [])
 */
class ExclusiveJobStorage extends Storage
{
    /**
     * @param string $name
     *
     * @return ExclusiveJob
     */
    public function getOneByName(string $name):ExclusiveJob
    {
        if (false == $exclusiveJob = $this->findOne(['name' => $name])) {
            throw new \LogicException(sprintf('The exclusive job with name "%s" could not be found', $name));
        }

        return $exclusiveJob;
    }

    /**
     * @param string $name
     * @param callable $lockCallback
     *
     * @return mixed
     */
    public function lockByName(string $name, callable $lockCallback)
    {
        $exclusiveJob = $this->getOneByName($name);

        return $this->lock(get_object_id($exclusiveJob), $lockCallback);
    }
}
