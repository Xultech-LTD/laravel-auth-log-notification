<?php

use Illuminate\Container\Container;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Facade;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\NotificationServiceProvider;
use Illuminate\Contracts\Notifications\Dispatcher;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Illuminate\Auth\AuthServiceProvider;
use Illuminate\Support\Facades\Hash;
use Illuminate\Hashing\BcryptHasher;
use Tests\TestApplication;

require __DIR__ . '/../vendor/autoload.php';

// Create container
$container = new TestApplication();

// Bind config
$config = new Repository([
    'authlog' => [
        'hooks' => [
            'on_login' => null,
            'on_logout' => null,
            'on_failed' => null,
        ],
        'lockout' => [
            'enabled' => true,
            'key_prefix' => 'authlog:test:',
            'max_attempts' => 3,
            'lockout_minutes' => 1,
            'track_by' => 'email',
        ],
    ],
]);
$container->instance('config', $config);

// Cache
$cache = new CacheRepository(new ArrayStore());
$container->instance('cache', $cache);

// Logger
$container->instance('log', new NullLogger());

// Set facades
Container::setInstance($container);
Facade::setFacadeApplication($container);

// Session
$sessionHandler = new ArraySessionHandler(120);
$session = new SessionStore('test', $sessionHandler);
$session->start();
$container->instance('session', $session);
$container->instance('session.store', $session);
Session::swap($session);

// App binding
$container->singleton('app', fn () => $container);
App::setFacadeApplication($container);

// Eloquent
$db = new DB();
$db->addConnection([
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
]);
$db->setAsGlobal();
$db->bootEloquent();
$container->instance('db', $db);
$container->instance('db.schema', $db->schema());
$container->instance('db.connection', $db->getConnection());

// Request
$request = HttpRequest::createFromBase(
    SymfonyRequest::create('/login', 'POST', [], [], [], [
        'HTTP_USER_AGENT' => 'Mozilla/5.0',
        'REMOTE_ADDR' => '192.168.1.1',
    ])
);
$container->instance('request', $request);

function request() {
    return Container::getInstance()->get('request');
}

// Notifications
(new NotificationServiceProvider($container))->register();

// Authentication
(new AuthServiceProvider(app()))->register();

$config->set('auth.guards.web', [
    'driver' => 'session',
    'provider' => 'users',
]);

$config->set('auth.providers.users', [
    'driver' => 'eloquent',
    'model' => Tests\Stubs\UserStub::class,
]);

$config->set('auth.passwords.users', [
    'provider' => 'users',
    'table' => 'password_resets',
    'expire' => 60,
]);

// Bind hashing
$container->singleton('hash', fn () => new BcryptHasher());
Hash::swap($container->make('hash'));

// Notification Dispatcher
$container->singleton(Dispatcher::class, fn ($app) => new ChannelManager($app));
$container->alias(Dispatcher::class, 'mailer');
$container->alias(ChannelManager::class, Dispatcher::class);

// Helpers
function config(string $key = null, $default = null) {
    $repo = Container::getInstance()->get('config');
    return $key ? $repo->get($key, $default) : $repo;
}

function app($abstract = null) {
    return is_null($abstract)
        ? Container::getInstance()
        : Container::getInstance()->make($abstract);
}

// Cleanup
afterEach(function () {
    Mockery::close();
});
