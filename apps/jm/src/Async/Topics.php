<?php
namespace App\Async;

class Topics
{
    const CREATE_JOB = 'job_manager.create_job';

    const UPDATE_JOB = 'job_manager.update_job';

    const JOB_TEMPLATE_UPDATED = 'comrade.job_template_updated';

    const CREATE_SUB_JOBS = 'job_manager.create_sub_jobs';

    const SCHEDULE_PROCESS = 'job_manager.schedule_process';

    const JOB_RESULT = 'job_result';

    const INTERNAL_ERROR = 'job_manager.internal_error';
}