<?php

namespace App;

use App\Async\CreateJob;
use App\Async\ExecuteJob;
use App\Async\ProcessFeedback;
use App\Infra\DependencyInjection\RegisterPvmBehaviorPass;
use App\Infra\Yadm\ObjectBuilderHook;
use App\Model\Job;
use App\Model\JobFeedback;
use App\Model\Process;
use function Makasim\Values\register_cast_hooks;
use function Makasim\Values\register_hook;
use function Makasim\Values\register_object_hooks;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function getCacheDir(): string
    {
        return dirname(__DIR__).'/var/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return dirname(__DIR__).'/var/logs';
    }

    public function registerBundles(): iterable
    {
        $contents = require dirname(__DIR__).'/etc/bundles.php';
        foreach ($contents as $class => $envs) {
            if (isset($envs['all']) || isset($envs[$this->environment])) {
                yield new $class();
            }
        }
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
        $confDir = dirname(__DIR__).'/etc';
        $loader->load($confDir.'/packages/*'.self::CONFIG_EXTS, 'glob');
        if (is_dir($confDir.'/packages/'.$this->environment)) {
            $loader->load($confDir.'/packages/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        }
        $loader->load($confDir.'/container'.self::CONFIG_EXTS, 'glob');

        $container->addCompilerPass(new RegisterPvmBehaviorPass());
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = dirname(__DIR__).'/etc';
        if (is_dir($confDir.'/routing/')) {
            $routes->import($confDir.'/routing/*'.self::CONFIG_EXTS, '/', 'glob');
        }
        if (is_dir($confDir.'/routing/'.$this->environment)) {
            $routes->import($confDir.'/routing/'.$this->environment.'/**/*'.self::CONFIG_EXTS, '/', 'glob');
        }
        $routes->import($confDir.'/routing'.self::CONFIG_EXTS, '/', 'glob');
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
            JobFeedback::SCHEMA => JobFeedback::class,
            CreateJob::SCHEMA => CreateJob::class,
            ExecuteJob::SCHEMA => ExecuteJob::class,
            ProcessFeedback::SCHEMA => ProcessFeedback::class,
        ]))->register();
    }
}
