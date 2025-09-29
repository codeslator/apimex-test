<?php

use App\Middleware\ValidationExceptionMiddleware;
//use App\Middleware\AuthMiddleware;
use Selective\BasePath\BasePathMiddleware;
use Slim\App;
use Slim\Middleware\ErrorMiddleware;

return function (App $app) {
    $app->addBodyParsingMiddleware();
    $app->add(ValidationExceptionMiddleware::class);
    //$app->add(AuthMiddleware::class);
    $app->addRoutingMiddleware();
    $app->add(\App\Middleware\CorsMiddleware::class);
    $app->add(BasePathMiddleware::class);
    $app->add(ErrorMiddleware::class);
};
