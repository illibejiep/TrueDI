<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use \Symfony\Bridge\ProxyManager\LazyProxy\Instantiator\RuntimeInstantiator;

require_once('../vendor/autoload.php');

$container = new ContainerBuilder();
$container->setProxyInstantiator(new RuntimeInstantiator());
$loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../config'));
$loader->load('services.yml');

$response = $container->get('response');
$response->send();
