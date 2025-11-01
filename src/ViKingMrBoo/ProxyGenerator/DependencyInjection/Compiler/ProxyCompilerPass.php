<?php

namespace ViKingMrBoo\ProxyGenerator\DependencyInjection\Compiler;

use ViKingMrBoo\ProxyGenerator\Annotation\ApiClient;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ProxyCompilerPass implements CompilerPassInterface
{
    private $annotationReader;

    public function __construct(AnnotationReader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('viking_proxy_generator.proxy_integration_service')) {
            return;
        }

        $proxyIntegrationService = $container->getDefinition('viking_proxy_generator.proxy_integration_service');
        $clientsConfig = $container->getParameter('viking_proxy_generator.clients');

        $reflectionClass = new \ReflectionClass(ApiClient::class);
        $annotationClass = $reflectionClass->getName();

        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();
            if (null === $class) {
                continue;
            }

            $reflectionClass = new \ReflectionClass($class);
            if (!$reflectionClass->isInterface()) {
                continue;
            }

            $annotation = $this->annotationReader->getClassAnnotation($reflectionClass, $annotationClass);
            if (null === $annotation) {
                continue;
            }

            $clientName = $annotation->value;
            if (!isset($clientsConfig[$clientName])) {
                throw new \InvalidArgumentException(sprintf('Client "%s" is not configured in viking_proxy_generator.clients.', $clientName));
            }

            $config = $clientsConfig[$clientName];
            $proxyClassName = $proxyIntegrationService->getProxyInstance($class, $config);

            $definition = new Definition($proxyClassName);
            $definition->setAutowired(true);
            $definition->setAutoconfigured(true);

            $container->setDefinition($id, $definition);
        }
    }
}