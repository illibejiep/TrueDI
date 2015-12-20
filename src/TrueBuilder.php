<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Bridge\ProxyManager\LazyProxy\Instantiator\RuntimeInstantiator;


class TrueBuilder {

    public static function buildContainer($rootPath)
    {
        $container = new ContainerBuilder();
        $container->setProxyInstantiator(new RuntimeInstantiator());
        $container->setParameter('app_root', $rootPath);
        $loader = new YamlFileLoader(
            $container,
            new FileLocator($rootPath . '/config')
        );
        $loader->load('services.yml');
        $container->compile();

        return $container;
    }
}