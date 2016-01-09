<?php

namespace CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RouterTagCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $routeTags = $container->findTaggedServiceIds('route');

        $collectionTags = $container->findTaggedServiceIds('route_collection');

        /** @var Definition[] $routeCollections */
        $routeCollections = array();
        foreach ($collectionTags as $serviceName => $tagData)
            $routeCollections[] = $container->getDefinition($serviceName);

        foreach ($routeTags as $routeServiceName => $tagData) {
            $routeName = null;
            if (isset($tagData[0]['route_name']))
                $routeName = $tagData[0]['route_name'];

            if (!$routeName)
                continue;

            $routeReference = new Reference($routeServiceName);
            foreach ($routeCollections as $collection)
                $collection->addMethodCall('add', array($routeName, $routeReference));
        }
    }

} 