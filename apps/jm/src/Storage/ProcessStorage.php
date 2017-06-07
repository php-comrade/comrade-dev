<?php
namespace App\Storage;

use App\Model\Process;
use Makasim\Yadm\Storage;

/**
 * @method Process|null create()
 * @method Process|null findOne(array $filter = [], array $options = [])
 * @method Process[]|\Traversable find(array $filter = [], array $options = [])
 */
class ProcessStorage extends Storage
{
    /**
     * @param string $id
     *
     * @return Process
     */
    public function getOneById(string $id):Process
    {
        if (false == $process = $this->findOne(['id' => $id])) {
            throw new \LogicException(sprintf('The process with id "%s" could not be found', $id));
        }

        return $process;
    }

    /**
     * @param string $token
     *
     * @return Process
     */
    public function getOneByToken(string $token):Process
    {
        if (false == $process = $this->findOne(['tokens.'.$token => ['$exists' => true]])) {
            throw new \LogicException(sprintf('The process with token "%s" could not be found', $token));
        }

        return $process;
    }
}
