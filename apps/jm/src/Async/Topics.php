<?php
namespace App\Async;

class Topics
{
    const CREATE_JOB = 'job_manager.create_job';

    const CREATE_SUB_JOBS = 'job_manager.create_sub_jobs';

    const SCHEDULE_JOB = 'job_manager.schedule_job';

    const JOB_RESULT = 'job_result';

    const PVM_HANDLE_ASYNC_TRANSITION = 'pvm.handle_async_transition';
}