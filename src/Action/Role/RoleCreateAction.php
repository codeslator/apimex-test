<?php

namespace App\Action\Role;

use App\Domain\Role\Service\RoleService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RoleCreateAction
{
  private JsonRenderer $renderer;

  private RoleService $service;

  public function __construct(RoleService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $this->service->create($data);
    return $this->renderer->json($response, ['message' => 'Role created.'])->withStatus(StatusCodeInterface::STATUS_CREATED);
  }
}
