<?php
namespace App\Async;

class Topics
{
    const CREATE_JOB = 'job_manager.create_job';

    const SCHEDULE_JOB = 'job_manager.schedule_job';

    const PROCESS_FEEDBACK = 'job_manager.process_feedback';

    const PVM_HANDLE_ASYNC_TRANSITION = 'pvm.handle_async_transition';
}