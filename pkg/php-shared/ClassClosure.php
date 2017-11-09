<?php
namespace Comrade\Shared;

use Comrade\Shared\Message\CreateJob;
use Comrade\Shared\Message\GetDependentJobs;
use Comrade\Shared\Message\GetDependentJobsResult;
use Comrade\Shared\Message\GetJob;
use Comrade\Shared\Message\GetJobChart;
use Comrade\Shared\Message\GetSubJobs;
use Comrade\Shared\Message\GetTimeline;
use Comrade\Shared\Message\GetTriggers;
use Comrade\Shared\Message\RunJob;
use Comrade\Shared\Message\RunSubJobsResult;
use Comrade\Shared\Message\ScheduleJob;
use Comrade\Shared\Message\RunnerResult;
use Comrade\Shared\Message\SearchTemplates;
use Comrade\Shared\Message\SearchTemplatesResult;
use Comrade\Shared\Model\CronTrigger;
use Comrade\Shared\Model\DependentJobTrigger;
use Comrade\Shared\Model\ExclusivePolicy;
use Comrade\Shared\Model\GracePeriodPolicy;
use Comrade\Shared\Model\HttpRunner;
use Comrade\Shared\Model\Job;
use Comrade\Shared\Model\JobResult;
use Comrade\Shared\Model\JobResultMetrics;
use Comrade\Shared\Model\JobTemplate;
use Comrade\Shared\Model\NowTrigger;
use Comrade\Shared\Model\QueueRunner;
use Comrade\Shared\Model\RetryFailedPolicy;
use Comrade\Shared\Model\RunDependentJobPolicy;
use Comrade\Shared\Model\RunSubJobsPolicy;
use Comrade\Shared\Model\SimpleTrigger;
use Comrade\Shared\Model\SubJob;
use Comrade\Shared\Model\SubJobPolicy;
use Comrade\Shared\Model\SubJobTemplate;
use Comrade\Shared\Message\JobResult as JobResultMessage;
use Comrade\Shared\Model\SubJobTrigger;
use Comrade\Shared\Model\Throwable;

final class ClassClosure
{
    const CLASS_MAP = [
        // models
        Job::SCHEMA => Job::class,
        JobTemplate::SCHEMA => JobTemplate::class,
        JobResult::SCHEMA => JobResult::class,
        JobResultMetrics::SCHEMA => JobResultMetrics::class,
        SubJobTemplate::SCHEMA => SubJobTemplate::class,
        SubJob::SCHEMA => SubJob::class,
        Throwable::SCHEMA => Throwable::class,
        GracePeriodPolicy::SCHEMA => GracePeriodPolicy::class,
        RetryFailedPolicy::SCHEMA => RetryFailedPolicy::class,
        RunSubJobsPolicy::SCHEMA => RunSubJobsPolicy::class,
        ExclusivePolicy::SCHEMA => ExclusivePolicy::class,
        SubJobPolicy::SCHEMA => SubJobPolicy::class,
        QueueRunner::SCHEMA => QueueRunner::class,
        HttpRunner::SCHEMA => HttpRunner::class,
        CronTrigger::SCHEMA => CronTrigger::class,
        SimpleTrigger::SCHEMA => SimpleTrigger::class,
        NowTrigger::SCHEMA => NowTrigger::class,
        SubJobTrigger::SCHEMA => SubJobTrigger::class,
        DependentJobTrigger::SCHEMA => DependentJobTrigger::class,
        RunDependentJobPolicy::SCHEMA => RunDependentJobPolicy::class,

        // messages
        RunSubJobsResult::SCHEMA => RunSubJobsResult::class,
        JobResultMessage::SCHEMA => JobResultMessage::class,
        CreateJob::SCHEMA => CreateJob::class,
        RunJob::SCHEMA => RunJob::class,
        GetTimeline::SCHEMA => GetTimeline::class,
        ScheduleJob::SCHEMA => ScheduleJob::class,
        \Comrade\Shared\Message\Part\SubJob::SCHEMA => \Comrade\Shared\Message\Part\SubJob::class,
        GetJob::SCHEMA => GetJob::class,
        GetSubJobs::SCHEMA => GetSubJobs::class,
        GetJobChart::SCHEMA => GetJobChart::class,
        RunnerResult::SCHEMA => RunnerResult::class,
        GetTriggers::SCHEMA => GetTriggers::class,
        SearchTemplates::SCHEMA => SearchTemplates::class,
        SearchTemplatesResult::SCHEMA => SearchTemplatesResult::class,
        GetDependentJobs::SCHEMA => GetDependentJobs::class,
        GetDependentJobsResult::SCHEMA => GetDependentJobsResult::class,
    ];

    /**
     * @var ClassClosure
     */
    private static $instance;

    public function __invoke(array $values): ?string
    {
        if (array_key_exists('schema', $values) && array_key_exists($values['schema'], self::CLASS_MAP)) {
            return self::CLASS_MAP[$values['schema']];
        }

        return null;
    }

    public static function create(): ClassClosure
    {
        if (false == self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}