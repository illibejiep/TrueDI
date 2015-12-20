<?php

require_once('../vendor/autoload.php');

$container = TrueBuilder::buildContainer(dirname(__DIR__));

$response = $container->get('response');
$response->send();
