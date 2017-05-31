<?php
namespace App\Async;

class Topics
{
    const CREATE_JOB = 'job_manager.create_job';

    const SCHEDULE_JOB = 'job_manager.schedule_job';

    const EXECUTE_JOB = 'job_manager.execute_job';
}