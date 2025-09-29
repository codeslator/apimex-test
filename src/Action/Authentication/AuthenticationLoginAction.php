<?php

namespace App\Action\Authentication;

use App\Domain\Authentication\Service\AuthenticationService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthenticationLoginAction
{
  private JsonRenderer $renderer;

  private AuthenticationService $service;

  public function __construct(AuthenticationService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $login = $this->service->loginByCredentials($data);
    return $this->renderer->response($response, $login)->withStatus(StatusCodeInterface::STATUS_OK);
  }
}
