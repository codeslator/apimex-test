<?php

namespace App\Action\Permission;

use App\Domain\Permission\Service\PermissionService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
final class PermissionGetByIdAction
{
  private JsonRenderer $renderer;
  private PermissionService $service;

  public function __construct(PermissionService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    try {
      $id = (int)$args['id'];
      $permission = $this->service->getById($id);
      return $this->renderer->response($response, $permission);
    } catch (\PDOException $e) {
      return $this->renderer->json($response, false);

    }
  }

}
