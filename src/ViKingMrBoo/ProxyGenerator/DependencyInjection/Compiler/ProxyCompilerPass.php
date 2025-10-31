<?php

namespace ViKingMrBoo\ProxyGenerator\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ProxyCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('mega_coder_proxy_generator.proxy_integration_service')) {
            return;
        }

        $proxyIntegrationService = $container->getDefinition('mega_coder_proxy_generator.proxy_integration_service');
        $services = $container->findTaggedServiceIds('mega_coder.proxy');

        foreach ($services as $id => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['interface'])) {
                    throw new \InvalidArgumentException('Tagged service "' . $id . '" must have an "interface" attribute.');
                }

                $interfaceName = $attributes['interface'];
                $config = $container->getParameterBag()->resolveValue($attributes['config']);

                $proxyClassName = $proxyIntegrationService->getProxyInstance($interfaceName, $config);

                $definition = new Definition($proxyClassName);
                $definition->setAutowired(true);
                $definition->setAutoconfigured(true);

                $container->setDefinition($id, $definition);
            }
        }
    }
}