<?php
namespace App\Storage;

use App\Model\PvmProcess;

/**
 * @method PvmProcess|null create()
 * @method PvmProcess|null findOne(array $filter = [], array $options = [])
 * @method PvmProcess[]|\Traversable find(array $filter = [], array $options = [])
 */
class ProcessExecutionStorage extends ProcessStorage
{
    public function getOneByJobId(string $jobId): PvmProcess
    {
        if (false == $process = $this->findOne(['jobId' => $jobId])) {
            throw new \LogicException(sprintf('The process for job "%s" could not be found', $jobId));
        }

        return $process;
    }
}
