<?php
namespace App;

use App\Model\JobResult;

class JobStatus
{
    const STATUS_NEW = 1;

    const STATUS_RUNNING = 2;

    const STATUS_RUN_EXCLUSIVE = 2 | 512;

    const STATUS_RUNNING_SUB_JOBS = 2 | 16;

    const STATUS_RUN_SUB_JOBS = 2 | 256;

    const STATUS_DONE = 4;

    const STATUS_CANCELED = 4 | 8;

    const STATUS_COMPLETED = 4 | 32;

    const STATUS_FAILED = 4 | 64;

    const STATUS_TERMINATED = 4 | 128;

    public static function isNew(JobResult $result):bool
    {
        return static::isEqual(static::STATUS_NEW, $result->getStatus());
    }

    public static function isRunning(JobResult $result):bool
    {
        return static::isEqual(static::STATUS_RUNNING, $result->getStatus());
    }

    public static function isDone(JobResult $result):bool
    {
        return static::isEqual(static::STATUS_DONE, $result->getStatus());
    }

    public static function isCanceled(JobResult $result):bool
    {
        return static::isSame(static::STATUS_CANCELED, $result->getStatus());
    }

    public static function isCompleted(JobResult $result):bool
    {
        return static::isSame(static::STATUS_COMPLETED, $result->getStatus());
    }

    public static function isFailed(JobResult $result):bool
    {
        return static::isSame(static::STATUS_FAILED, $result->getStatus());
    }

    public static function isRunSubJobs(JobResult $result):bool
    {
        return static::isSame(static::STATUS_RUN_SUB_JOBS, $result->getStatus());
    }

    public static function isRunningSubJobs(JobResult $result):bool
    {
        return static::isSame(static::STATUS_RUNNING_SUB_JOBS, $result->getStatus());
    }

    public static function isTerminated(JobResult $result):bool
    {
        return static::isSame(static::STATUS_TERMINATED, $result->getStatus());
    }

    /**
     * @param int $expectedStatus
     * @param int $actualStatus
     *
     * @return bool
     */
    private static function isEqual(int $expectedStatus, int $actualStatus):bool
    {
        return ($expectedStatus | $actualStatus) === $expectedStatus;
    }

    /**
     * @param int $expectedStatus
     * @param int $actualStatus
     *
     * @return bool
     */
    private static function isSame(int $expectedStatus, int $actualStatus):bool
    {
        return $expectedStatus === $actualStatus;
    }
}
