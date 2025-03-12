<?php

use Illuminate\Container\Container;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Facade;

require __DIR__ . '/../vendor/autoload.php';

// Create a new Laravel-style container
$container = new Container();

// Bind a config repository manually
$config = new Repository([
    'authlog' => [
        'hooks' => [
            'on_login' => null,
            'on_logout' => null,
            'on_failed' => null,
        ],
    ],
]);

$container->instance('config', $config);

// Set the container so we can use it manually elsewhere
Container::setInstance($container);
Facade::setFacadeApplication($container);


// Global helper if needed later
function config(string $key = null, $default = null) {
    $repo = Container::getInstance()->get('config');
    return $key ? $repo->get($key, $default) : $repo;
}
