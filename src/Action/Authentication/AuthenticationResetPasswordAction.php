<?php

namespace App\Action\Authentication;

use App\Domain\Authentication\Service\AuthenticationService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthenticationResetPasswordAction
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
    $status = $this->service->resetPassword($data['email']);
    return $this->renderer->json($response, ['reset' => $status])->withStatus($status ? StatusCodeInterface::STATUS_OK : StatusCodeInterface::STATUS_NOT_FOUND);
  }
}
