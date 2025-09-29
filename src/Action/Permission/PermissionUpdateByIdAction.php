<?php

namespace App\Action\Permission;

use App\Domain\Permission\Service\PermissionService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PermissionUpdateByIdAction
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
    $data = (array)$request->getParsedBody();
    $permissionId = (int)$args["id"];
    $this->service->update($permissionId, $data);
    return $this->renderer->json($response, ['message' => 'Permission updated successfully.'])->withStatus(StatusCodeInterface::STATUS_CREATED);
  }
}
