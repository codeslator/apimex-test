<?php

namespace App\Handler;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Slim\Views\PhpRenderer;
use Throwable;

final class NotFoundHandler implements ErrorHandlerInterface
{


    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails,
    ): ResponseInterface {

        $response = new Response();
        $templates = new \League\Plates\Engine(__DIR__ . '/../../templates');
        $template  = $templates->render('notFoundTemplate');
        $response->getBody()->write((string)$template);

        return $response->withStatus(404);
    }
}
