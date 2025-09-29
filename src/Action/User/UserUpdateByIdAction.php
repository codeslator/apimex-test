<?php

namespace App\Action\User;

use App\Domain\User\Service\UserService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UserUpdateByIdAction
{
  private JsonRenderer $renderer;

  private UserService $service;

  public function __construct(UserService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $userId = (int)$args["id"];
    $this->service->update($userId, $data);
    return $this->renderer->json($response, ['message' => 'User updated.'])->withStatus(StatusCodeInterface::STATUS_CREATED);
  }
}
