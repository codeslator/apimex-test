<?php

namespace App\Action\Authentication;

use App\Domain\Authentication\Service\AuthenticationService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthenticationRegisterAction
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
    $this->service->register($data);
    return $this->renderer->json($response, ['message' => 'User registered.'])->withStatus(StatusCodeInterface::STATUS_CREATED);
  }
}
