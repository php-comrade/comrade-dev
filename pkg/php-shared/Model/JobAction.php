<?php
namespace Comrade\Shared\Model;

class JobAction
{
    const RUN = 'run';

    const COMPLETE = 'complete';

    const FAIL = 'fail';

    const CANCEL = 'cancel';

    const TERMINATE = 'terminate';

    const RUN_SUB_JOBS = 'run_sub_jobs';

    /**
     * @return string[]
     */
    public static function getActions(): array
    {
        return [self::RUN, self::COMPLETE, self::FAIL, self::CANCEL, self::TERMINATE, self::RUN_SUB_JOBS];
    }
}
