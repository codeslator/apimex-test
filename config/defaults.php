<?php

// Application default settings

// Error reporting
error_reporting(0);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Timezone
date_default_timezone_set('Europe/Berlin');

$settings = [];

// Error handler
$settings['error'] = [
    // Should be set to false for the production environment
    'display_error_details' => false,
    // Should be set to false for the test environment
    'log_errors' => true,
    // Display error details in error log
    'log_error_details' => true,
];

// Logger settings
$settings['logger'] = [
    // Log file location
    'path' => __DIR__ . '/../logs',
    // Default log level
    'level' => \Monolog\Level::Info,
];

// Database settings
$settings['db'] = [
    //'driver' => \Cake\Database\Driver\Mysql::class,
    'driver' => 'mysql',
    'host' => 'localhost',
    'encoding' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    // Enable identifier quoting
    'quoteIdentifiers' => true,
    // Set to null to use MySQL servers timezone
    'timezone' => null,
    // Disable meta data cache
    'cacheMetadata' => false,
    // Disable query logging
    'log' => false,
    // Turn off persistent connections
    'persistent' => false,
    // PDO options
    'flags' => [
        // Turn off persistent connections
        PDO::ATTR_PERSISTENT => false,
        // Enable exceptions
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // Emulate prepared statements
        PDO::ATTR_EMULATE_PREPARES => true,
        // Set default fetch mode to array
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Convert numeric values to strings when fetching.
        // Since PHP 8.1 integers and floats in result sets will be returned using native PHP types.
        // This option restores the previous behavior.
        PDO::ATTR_STRINGIFY_FETCHES => true,
    ],
];

$settings['rate_limiter'] = [
    // A unique id for the api rate limiter
    'id' => 'my_api',

    // Valid values: token_bucket, fixed_window, sliding_window
    // See: https://symfony.com/doc/current/rate_limiter.html#rate-limiting-policies
    'policy' => 'fixed_window',

    // Max 1000 request per minute
    'limit' => 300,

    // The time window for the limit
    'interval' => '60 seconds',

    // The main cache directory (the application needs read-write permissions on it)
    // if none is specified, a directory is created inside the system temporary directory
    'cache_directory' => null,

    // A string used as the subdirectory of the root cache directory,
    // where cache items will be stored
    'cache_namespace' => 'fv_mx',

    // The default lifetime (in seconds) for cache items that do not define their
    // own lifetime, with a value 0 causing items to be stored indefinitely (i.e.
    // until the files are deleted)
    'cache_default_lifetime' => 0,

    // The directory to store the lock,
    // defaults to the systems temporary directory
    'lock_path' => null,
];

// $settings['view'] = [
//     // Path to templates
//     'path' => __DIR__ . '/../templates',
//     // Default attributes
//     'attributes' => [],
// ];

// $settings['storage'] = [
//     'adapter' => \League\Flysystem\Local\LocalFilesystemAdapter::class,
//     'config' => [
//         'root' => realpath(__DIR__ . '/../storage'),
//         'permissions' => [
//             'file' => [
//                 'public' => 0755,
//                 'private' => 0755,
//             ],
//             'dir' => [
//                 'public' => 0755,
//                 'private' => 0755,
//             ],
//         ],
//         'visibility' => \League\Flysystem\Visibility::PUBLIC,
//         'lock' => LOCK_EX,
//         'link' => \League\Flysystem\Local\LocalFilesystemAdapter::DISALLOW_LINKS,
//     ],
// ];

// $settings['storage'] = [
//     'adapter' => \League\Flysystem\PhpseclibV3\SftpAdapter::class,
//     'config' => [
//         // host (required)
//         'host' => 'https://eu2.contabostorage.com/firmavirtual.bucket.001',
//         // port (optional, default: 22)
//         'port' => 22,
//         // username (required)
//         'username' => 'contacto@josecortesia.cl',
//         // password (optional, default: null) set to null if privateKey is used
//         'password' => 'C0rtesia19',
//         'private_key' => null, //'e9a5857cf5ba8f5516e6d1bc00b00966',
//         'passphrase' => null,
//         'root' => '/',
//         'timeout' => 10,
//     ],
// ];

// $settings['aws'] = [
//     // 'adapter' => \League\Flysystem\AwsS3V3\AwsS3V3Adapter::class,
//     'config' => [
//         'region' => $_SERVER['AWS_DEFAULT_REGION'], // Reemplaza 'us-east-1' con la región correspondiente a tu bucket en Amazon S3.
//         'version' => 'latest',
//         //'http'    => ['decode_content' => false],
//         'credentials' => [
//             'key' => $_SERVER['AWS_ACCESS_KEY_ID'],
//             'secret' => $_SERVER['AWS_SECRET_ACCESS_KEY'],
//         ],
//     ],
// ];

// Console commands
$settings['commands'] = [
    \App\Console\ExampleCommand::class,
    \App\Console\SetupCommand::class,
];

return $settings;
