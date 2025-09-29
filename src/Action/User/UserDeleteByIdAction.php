<?php

namespace App\Action\User;

use App\Domain\User\Service\UserService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
final class UserDeleteByIdAction
{
  private JsonRenderer $renderer;
  private UserService $service;

  public function __construct(UserService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
      $id = (int)$args['id'];
      $this->service->delete($id);
      return $this->renderer->response($response, ['message' => 'User deleted.']);
  }

}
