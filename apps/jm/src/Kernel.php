<?php

namespace App;

use App\Async\AddTrigger;
use App\Async\CreateJob;
use App\Async\GetJob;
use App\Async\GetSubJobs;
use App\Async\GetTimeline;
use App\Async\RunSubJobsResult;
use App\Async\RunJob;
use App\Async\JobResult;
use App\Async\ScheduleJob;
use App\Infra\DependencyInjection\RegisterPvmBehaviorPass;
use App\Infra\Yadm\ObjectBuilderHook;
use App\Model\CronTrigger;
use App\Model\ExclusivePolicy;
use App\Model\GracePeriodPolicy;
use App\Model\Job;
use App\Model\JobResult as JobResultModel;
use App\Model\JobTemplate;
use App\Model\NowTrigger;
use App\Model\QueueRunner;
use App\Model\Process;
use App\Model\RetryFailedPolicy;
use App\Model\RunSubJobsPolicy;
use App\Model\SimpleTrigger;
use App\Model\SubJob;
use App\Model\SubJobTemplate;
use function Makasim\Values\register_cast_hooks;
use function Makasim\Values\register_hook;
use function Makasim\Values\register_object_hooks;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Voryx\ThruwayBundle\VoryxThruwayBundle;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function getCacheDir(): string
    {
        if ($cacheDir = getenv('APP_CACHE_DIR')) {
            return $cacheDir;
        }

        return dirname(__DIR__).'/var/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        if ($logDir = getenv('APP_LOG_DIR')) {
            return $logDir;
        }

        return dirname(__DIR__).'/var/logs';
    }

    public function registerBundles(): iterable
    {
        $contents = require dirname(__DIR__).'/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if (isset($envs['all']) || isset($envs[$this->environment])) {
                yield new $class();
            }
        }

        yield new VoryxThruwayBundle($this);
    }

    public function boot()
    {
        if (false == $this->booted) {
            $this->configureYadmHooks();
        }
        parent::boot();
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $confDir = dirname(__DIR__).'/config';
        $loader->load($confDir.'/packages/*'.self::CONFIG_EXTS, 'glob');
        if (is_dir($confDir.'/packages/'.$this->environment)) {
            $loader->load($confDir.'/packages/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        }
        $container->addCompilerPass(new RegisterPvmBehaviorPass());
        $loader->load($confDir.'/services'.self::CONFIG_EXTS, 'glob');
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = dirname(__DIR__).'/config';
        if (is_dir($confDir.'/routes/')) {
            $routes->import($confDir.'/routes/*'.self::CONFIG_EXTS, '/', 'glob');
        }
        if (is_dir($confDir.'/routes/'.$this->environment)) {
            $routes->import($confDir.'/routes/'.$this->environment.'/**/*'.self::CONFIG_EXTS, '/', 'glob');
        }
        $routes->import($confDir.'/routes'.self::CONFIG_EXTS, '/', 'glob');
    }

    public function configureYadmHooks()
    {
        register_cast_hooks();
        register_object_hooks();

        register_hook(Process::class, 'post_build_sub_object', function($object, $context, $contextKey) {
            if (method_exists($object, 'setProcess')) {
                $object->setProcess($context);
            }
        });

        (new ObjectBuilderHook([
            Job::SCHEMA => Job::class,
            JobTemplate::SCHEMA => JobTemplate::class,
            JobResult::SCHEMA => JobResult::class,
            SubJobTemplate::SCHEMA => SubJobTemplate::class,
            SubJob::SCHEMA => SubJob::class,
            RunSubJobsResult::SCHEMA => RunSubJobsResult::class,

            JobResultModel::SCHEMA => JobResultModel::class,
            CreateJob::SCHEMA => CreateJob::class,
            RunJob::SCHEMA => RunJob::class,
            AddTrigger::SCHEMA => AddTrigger::class,
            GetTimeline::SCHEMA => GetTimeline::class,
            ScheduleJob::SCHEMA => ScheduleJob::class,
            GetJob::SCHEMA => GetJob::class,
            GetSubJobs::SCHEMA => GetSubJobs::class,

            GracePeriodPolicy::SCHEMA => GracePeriodPolicy::class,
            RetryFailedPolicy::SCHEMA => RetryFailedPolicy::class,
            RunSubJobsPolicy::SCHEMA => RunSubJobsPolicy::class,
            ExclusivePolicy::SCHEMA => ExclusivePolicy::class,

            QueueRunner::SCHEMA => QueueRunner::class,

            CronTrigger::SCHEMA => CronTrigger::class,
            SimpleTrigger::SCHEMA => SimpleTrigger::class,
            NowTrigger::SCHEMA => NowTrigger::class,
        ]))->register();
    }
}
