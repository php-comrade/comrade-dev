<?php
namespace App;

use Formapro\Pvm\Enqueue\HandleAsyncTransitionProcessor;

final class Commands
{
    const CREATE_JOB = 'comrade_create_job';

    const CREATE_SUB_JOBS = 'comrade_create_sub_jobs';

    const SCHEDULE_JOB = 'comrade_schedule_job';

    const EXECUTE_PROCESS = 'comrade_execute_process';

    const JOB_RESULT = 'comrade_job_result';

    const PVM_HANDLE_ASYNC_TRANSITION = HandleAsyncTransitionProcessor::COMMAND;
}
