<?php
use Symfony\Component\Dotenv\Dotenv;
use App\Factory\DopplerFactory;
use GuzzleHttp\Client;

$envVars = null;
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

if (isset($_ENV['DOPPLER_KEY']) && isset($_ENV['DOPPLER_BRANCH']) && isset($_ENV['DOPPLER_PROJECT'])) {
    $dopplerFactory = new DopplerFactory(new Client(), $_ENV['DOPPLER_KEY'], $_ENV['DOPPLER_PROJECT'], $_ENV['DOPPLER_BRANCH']);
    $envVars = $dopplerFactory->fetchEnvironmentVariables();
    
    if (is_array($envVars)) {
        foreach ($envVars as $key => $value) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}
else {
    $envVars = $_ENV;
}


// Detect environment
$_ENV['APP_ENV'] ??= $_SERVER['APP_ENV'] ?? 'dev';

// Load default settings
$settings = require __DIR__ . '/defaults.php';

// Overwrite default settings with environment specific local settings
$configFiles = [
    __DIR__ . sprintf('/local.%s.php', $_ENV['APP_ENV']),
    __DIR__ . '/env.php',
    __DIR__ . '/../env.php',
];

foreach ($configFiles as $configFile) {
    if (!file_exists($configFile)) {
        continue;
    }

    $local = require $configFile;
    if (is_callable($local)) {
        $settings = $local($settings);
    }
}

return $settings;
