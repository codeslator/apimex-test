<?php

use App\Factory\LoggerFactory;
use App\Handler\DefaultErrorHandler;
use Cake\Database\Connection;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Selective\BasePath\BasePathMiddleware;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteParserInterface;
use Slim\Middleware\ErrorMiddleware;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use App\Database\PdoConnection;
use Slim\Views\PhpRenderer;
use App\Filesystem\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use App\Handler\NotFoundHandler;
use Slim\Exception\HttpNotFoundException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;


return [
    // Application settings
    'settings' => fn () => require __DIR__ . '/settings.php',

    App::class => function (ContainerInterface $container) {
        $app = AppFactory::createFromContainer($container);

        // Register routes
        (require __DIR__ . '/routes.php')($app);

        // Register middleware
        (require __DIR__ . '/middleware.php')($app);

        return $app;
    },

    // HTTP factories
    ResponseFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },

    ServerRequestFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },

    StreamFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },

    UploadedFileFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },

    UriFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },

    // The Slim RouterParser
    RouteParserInterface::class => function (ContainerInterface $container) {
        return $container->get(App::class)->getRouteCollector()->getRouteParser();
    },

    // The logger factory
    LoggerFactory::class => function (ContainerInterface $container) {
        return new LoggerFactory($container->get('settings')['logger']);
    },

    BasePathMiddleware::class => function (ContainerInterface $container) {
        return new BasePathMiddleware($container->get(App::class));
    },

    // Database connection
    Connection::class => function (ContainerInterface $container) {
        return new Connection($container->get('settings')['db']);
    },

    // PDO::class => function (ContainerInterface $container) {
    //     $db = $container->get(Connection::class);
    //     $driver = $db->getDriver();
    //     $driver->connect();

    //     return $driver->getConnection();
    // },

    PdoConnection::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['db'];
        $database = $settings['database'];
        $driver = $settings['driver'] ?? 'mysql';
        $host = $settings['host'] ?? '127.0.0.1';
        $port = $settings['port'] ?? '3306';
        $username = (string)$settings['username'];
        $password = (string)$settings['password'];
        // $charset = (string)$settings['charset'] ?? 'utf8mb4';
        // $collate = (string)$settings['collate'] ?? 'utf8mb4_unicode_ci';
        $flags = (array)$settings['flags'];
        $dsn = "$driver:host=$host:$port;dbname=$database";

        return new PdoConnection($dsn, $username, $password, $flags);
    },

    PhpRenderer::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['view'];

        return new PhpRenderer($settings['path'], $settings['attributes']);
    },

    RateLimiterFactory::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['rate_limiter'];

        $directory = $settings['cache_directory'] ?? null;
        $namespace = $settings['cache_namespace'] ?? '';
        $defaultLifetime = $settings['cache_default_lifetime'] ?? 0;
        $lockPath = $settings['lock_path'] ?? null;

        $cache = new FilesystemAdapter($namespace, $defaultLifetime, $directory);
        $storage = new CacheStorage($cache);
        $lockFactory = new LockFactory(new FlockStore($lockPath));

        $config = [
            'id' => $settings['id'],
            'policy' => $settings['policy'],
            'limit' => $settings['limit'],
            'interval' => $settings['interval'],
        ];

        return new RateLimiterFactory($config, $storage, $lockFactory);
    },

    // S3Client::class => function (ContainerInterface $container) {
    //     // Read storage adapter settings
    //     $settings = $container->get('settings')['aws'];
    //     $config = $settings['config'];

    //     return new S3Client($config);
    // },

    // LocalFilesystemAdapter::class => function () {
    //     return function (array $config) {
    //         return new LocalFilesystemAdapter(
    //             $config['root'] ?? '',
    //             PortableVisibilityConverter::fromArray(
    //                 $config['permissions'] ?? [],
    //                 $config['visibility'] ?? Visibility::PUBLIC
    //             ),
    //             $config['lock'] ?? LOCK_EX,
    //             $config['link'] ?? LocalFilesystemAdapter::DISALLOW_LINKS
    //         );
    //     };
    // },

    // InMemoryFilesystemAdapter::class => function () {
    //     return function () {
    //         return new InMemoryFilesystemAdapter();
    //     };
    // },

    SftpAdapter::class => function () {
        return function (array $config) {
            return new SftpAdapter(
                new SftpConnectionProvider(
                    (string)$config['host'],
                    (string)$config['username'],
                    $config['password'] ?? null,
                    $config['private_key'] ?? null,
                    $config['passphrase'] ?? null,
                    $config['port'] ?? 22,
                    false,
                    $config['timeout'] ?? 10
                ),
                $config['root'] ?? ''
            );
        };
    },

    AwsS3V3Adapter::class => function () {
        return function (array $config) {
            new AwsS3V3Adapter(
                new S3Client($config['client']),
                (string)$config['bucket_name'],
                (string)$config['path_prefix'],
            );
        };
    },

    ErrorMiddleware::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['error'];
        $app = $container->get(App::class);

        $logger = $container->get(LoggerFactory::class)
            ->addFileHandler('error.log')
            ->createLogger();

        $errorMiddleware = new ErrorMiddleware(
            $app->getCallableResolver(),
            $app->getResponseFactory(),
            (bool)$settings['display_error_details'],
            (bool)$settings['log_errors'],
            (bool)$settings['log_error_details'],
            $logger
        );

        $errorMiddleware->setDefaultErrorHandler($container->get(DefaultErrorHandler::class));

        $errorMiddleware->setErrorHandler(HttpNotFoundException::class, NotFoundHandler::class);

        return $errorMiddleware;
    },

    Application::class => function (ContainerInterface $container) {
        $application = new Application();

        $application->getDefinition()->addOption(
            new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev')
        );

        foreach ($container->get('settings')['commands'] as $class) {
            $application->add($container->get($class));
        }

        return $application;
    },
];
