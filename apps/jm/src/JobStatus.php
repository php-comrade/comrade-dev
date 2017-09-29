<?php
namespace App;

use Comrade\Shared\Model\JobResult;

class JobStatus extends \Comrade\Shared\Model\JobStatus
{
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

    public static function getDoneStatuses():array
    {
        return [
            JobStatus::STATUS_DONE,
            JobStatus::STATUS_FAILED,
            JobStatus::STATUS_COMPLETED,
            JobStatus::STATUS_CANCELED,
            JobStatus::STATUS_TERMINATED
        ];
    }

    /**
     * @param int $expectedStatus
     * @param int $actualStatus
     *
     * @return bool
     */
    private static function isEqual(int $expectedStatus, int $actualStatus):bool
    {
        return ($expectedStatus | $actualStatus) === $actualStatus;
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
