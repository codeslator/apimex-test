<?php

namespace App\Action\Role;

use App\Domain\Role\Service\RoleService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Fig\Http\Message\StatusCodeInterface;

final class RoleGetAllAction
{
  private JsonRenderer $renderer;
  private RoleService $service;

  public function __construct(RoleService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $pagination = $request->getQueryParams();
    $roles = $this->service->getAll($pagination);
    return $this->renderer->json($response, $roles)->withStatus(StatusCodeInterface::STATUS_OK);
  }
}
