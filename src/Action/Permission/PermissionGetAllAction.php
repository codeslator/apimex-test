<?php

namespace App\Action\Permission;

use App\Domain\Permission\Service\PermissionService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Fig\Http\Message\StatusCodeInterface;

final class PermissionGetAllAction
{
  private JsonRenderer $renderer;
  private PermissionService $service;

  public function __construct(PermissionService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $pagination = $request->getQueryParams();
    $permissions = $this->service->getAll($pagination);
    return $this->renderer->json($response, $permissions)->withStatus(StatusCodeInterface::STATUS_OK);
  }
}
