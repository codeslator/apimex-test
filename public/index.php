<?php

use App\Emitter\CorsResponseEmitter;
use Slim\Factory\ServerRequestCreatorFactory;

$app = require __DIR__ . '/../config/bootstrap.php';

$request = ServerRequestCreatorFactory::create()->createServerRequestFromGlobals();
$response = $app->handle($request);
$responseEmitter = new CorsResponseEmitter();
$responseEmitter->emit($response);

// $app->run();
