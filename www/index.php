<?php

require_once('../vendor/autoload.php');

$container = TrueContainer::buildContainer(dirname(__DIR__));

$response = $container->get('response');
$response->send();
