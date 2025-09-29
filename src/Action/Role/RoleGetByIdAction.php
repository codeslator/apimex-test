<?php

namespace App\Action\Role;

use App\Domain\Role\Service\RoleService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
final class RoleGetByIdAction
{
  private JsonRenderer $renderer;
  private RoleService $service;

  public function __construct(RoleService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    try {
      $id = (int)$args['id'];
      $role = $this->service->getById($id);
      return $this->renderer->response($response, $role);
    } catch (\PDOException $e) {
      return $this->renderer->json($response, false);
    }
  }

}
