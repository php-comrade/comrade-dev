<?php
namespace App\Storage;

use App\Model\PvmProcess;
use Makasim\Yadm\Storage;

/**
 * @method PvmProcess|null create()
 * @method PvmProcess|null findOne(array $filter = [], array $options = [])
 * @method PvmProcess[]|\Traversable find(array $filter = [], array $options = [])
 */
class ProcessStorage extends Storage
{
    /**
     * @param string $id
     *
     * @return PvmProcess
     */
    public function getOneById(string $id):PvmProcess
    {
        if (false == $process = $this->findOne(['id' => $id])) {
            throw new \LogicException(sprintf('The process with id "%s" could not be found', $id));
        }

        return $process;
    }

    /**
     * @param string $token
     *
     * @return PvmProcess
     */
    public function getOneByToken(string $token):PvmProcess
    {
        if (false == $process = $this->findOne(['tokens.'.$token => ['$exists' => true]])) {
            throw new \LogicException(sprintf('The process with token "%s" could not be found', $token));
        }

        return $process;
    }
}
