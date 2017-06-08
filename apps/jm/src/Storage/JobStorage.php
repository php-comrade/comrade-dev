<?php
namespace App\Storage;

use App\Model\Job;
use function Makasim\Yadm\get_object_id;
use Makasim\Yadm\Storage;

/**
 * @method Job|null create()
 * @method Job|null findOne(array $filter = [], array $options = [])
 * @method Job[]|\Traversable find(array $filter = [], array $options = [])
 */
class JobStorage extends Storage
{
    /**
     * @param string $id
     * @return Job
     */
    public function getOneById(string $id):Job
    {
        if (false == $job = $this->findOne(['id' => $id])) {
            throw new \LogicException(sprintf('The job with id "%s" could not be found', $id));
        }

        return $job;
    }

    /**
     * @param string $jobId
     * @param callable $lockCallback
     *
     * @return mixed
     */
    public function lockByJobId(string $jobId, callable $lockCallback)
    {
        $job = $this->getOneById($jobId);

        return $this->lock(get_object_id($job), $lockCallback);
    }
}
