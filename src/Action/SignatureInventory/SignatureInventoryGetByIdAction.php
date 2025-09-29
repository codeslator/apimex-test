<?php

namespace App\Action\SignatureInventory;

use App\Domain\SignatureInventory\Service\SignatureInventoryService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
final class SignatureInventoryGetByIdAction
{
  private JsonRenderer $renderer;
  private SignatureInventoryService $service;

  public function __construct(SignatureInventoryService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    try {
      $id = (int)$args['id'];
      $inventoryItem = $this->service->getById($id);
      return $this->renderer->response($response, $inventoryItem);
    } catch (\PDOException $e) {
      return $this->renderer->json($response, false);

    }
  }

}
