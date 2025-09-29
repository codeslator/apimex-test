<?php

namespace App\Action\Permission;

use App\Domain\Permission\Service\PermissionService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PermissionDeleteByIdAction
{
  private JsonRenderer $renderer;

  private PermissionService $service;

  public function __construct(PermissionService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    $permissionId = (int)$args["id"];
    $this->service->delete($permissionId);
    return $this->renderer->json($response, ['message' => 'Permission deleted successfully.'])->withStatus(StatusCodeInterface::STATUS_CREATED);
  }
}
