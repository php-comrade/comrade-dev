<?php
namespace App\Infra\DependencyInjection;

use Formapro\Pvm\BehaviorRegistry;
use Formapro\Pvm\DefaultBehaviorRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterPvmBehaviorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (false == $container->hasDefinition(DefaultBehaviorRegistry::class)) {
            return;
        }

        $repository = $container->getDefinition(DefaultBehaviorRegistry::class);
        foreach ($container->findTaggedServiceIds('pvm.behavior') as $serviceId => $tagAttributes) {
            $repository->addMethodCall('register', [$serviceId, new Reference($serviceId)]);
        }
    }
}