<?php

namespace App\Action\User;

use App\Domain\User\Service\UserService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UserCheckExistsAction
{
  private JsonRenderer $renderer;

  private UserService $service;

  public function __construct(UserService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $exists = $this->service->checkUserExists($data);
    return $this->renderer->json($response, ['user_exists' => $exists])->withStatus(StatusCodeInterface::STATUS_OK);
  }
}
