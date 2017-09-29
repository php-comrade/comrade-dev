<?php
namespace Comrade\Shared\Model;

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
}
