<?php

namespace ViKingMrBoo\ProxyGenerator\DependencyInjection;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use ViKingMrBoo\ProxyGenerator\DependencyInjection\Compiler\ProxyCompilerPass;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class VikingProxyGeneratorExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $container->setParameter('viking_proxy_generator.cache_dir', $config['cache_dir']);
        $container->setParameter('viking_proxy_generator.proxies_dir', $config['proxies_dir']);
        $container->setParameter('viking_proxy_generator.clients', $config['clients']);

        // Регистрация кэша и HTTP клиента
        $container->register('viking_proxy_generator.cache_provider', FilesystemAdapter::class)
            ->setArguments([$config['cache_dir']]);

        $container->register('viking_proxy_generator.http_client', HttpClientInterface::class)
            ->setFactory([HttpClientInterface::class, 'create']);
    }
}