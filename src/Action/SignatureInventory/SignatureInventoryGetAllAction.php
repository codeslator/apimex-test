<?php

namespace App\Action\SignatureInventory;

use App\Domain\SignatureInventory\Service\SignatureInventoryService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SignatureInventoryGetAllAction
{
  private JsonRenderer $renderer;
  private SignatureInventoryService $service;

  public function __construct(SignatureInventoryService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $inventory = $this->service->getAll();
    return $this->renderer->response($response, $inventory);
  }
}
