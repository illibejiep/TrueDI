<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Bridge\ProxyManager\LazyProxy\Instantiator\RuntimeInstantiator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CompilerPass\RouterTagCompilerPass;

class TrueContainer extends ContainerBuilder {

    public static function buildContainer($rootPath)
    {
        $container = new self();
        $container->addCompilerPass(new RouterTagCompilerPass());
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

    public function get($id, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE)
    {
        if (strtolower($id) == 'service_container') {
            if (ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE !== $invalidBehavior) {
                return;
            }
            throw new InvalidArgumentException(sprintf('The service definition "%s" does not exist.', $id));
        }

        return parent::get($id, $invalidBehavior);
    }
}