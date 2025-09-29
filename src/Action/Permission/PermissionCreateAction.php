<?php

namespace App\Action\Permission;

use App\Domain\Permission\Service\PermissionService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PermissionCreateAction
{
  private JsonRenderer $renderer;

  private PermissionService $service;

  public function __construct(PermissionService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $this->service->create($data);
    return $this->renderer->json($response, ['message' => 'Permission created.'])->withStatus(StatusCodeInterface::STATUS_CREATED);
  }
}
