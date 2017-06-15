<?php
namespace App\Async;

use Formapro\Pvm\Enqueue\HandleAsyncTransitionProcessor;

class Topics
{
    const CREATE_JOB = 'job_manager.create_job';

    const CREATE_SUB_JOBS = 'job_manager.create_sub_jobs';

    const SCHEDULE_PROCESS = 'job_manager.schedule_process';

    const JOB_RESULT = 'job_result';

    const PVM_HANDLE_ASYNC_TRANSITION = HandleAsyncTransitionProcessor::TOPIC;
}