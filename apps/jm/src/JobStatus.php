<?php
namespace App;

use Comrade\Shared\Model\JobResult;

class JobStatus extends \Comrade\Shared\Model\JobStatus
{
    public static function isNew(JobResult $result):bool
    {
        return static::isEqual(static::NEW, $result->getStatus());
    }

    public static function isRunning(JobResult $result):bool
    {
        return static::isEqual(static::RUNNING, $result->getStatus());
    }

    public static function isDone(JobResult $result):bool
    {
        return static::isEqual(static::STATUS_DONE, $result->getStatus());
    }

    public static function isCanceled(JobResult $result):bool
    {
        return static::isSame(static::CANCELED, $result->getStatus());
    }

    public static function isCompleted(JobResult $result):bool
    {
        return static::isSame(static::COMPLETED, $result->getStatus());
    }

    public static function isFailed(JobResult $result):bool
    {
        return static::isSame(static::FAILED, $result->getStatus());
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
        return static::isSame(static::TERMINATED, $result->getStatus());
    }

    public static function getDoneStatuses():array
    {
        return [
            JobStatus::FAILED,
            JobStatus::COMPLETED,
            JobStatus::CANCELED,
            JobStatus::TERMINATED
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
