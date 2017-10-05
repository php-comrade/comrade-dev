<?php
namespace Comrade\Shared\Model;

class JobStatus
{
    const NEW = 'new';

    const RUNNING = 'running';

    const RUNNING_SUB_JOBS = 'running_sub_jobs';

    const RETRYING = 'retrying';

    const CANCELED = 'canceled';

    const COMPLETED = 'completed';

    const FAILED = 'failed';

    const TERMINATED = 'terminated';
}
