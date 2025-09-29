<?php

namespace App\Action\SignatureInventory;

use App\Domain\SignatureInventory\Service\SignatureInventoryService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SignatureInventoryCreateAction
{
  private JsonRenderer $renderer;

  private SignatureInventoryService $service;

  public function __construct(SignatureInventoryService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $this->service->create($data);
    return $this->renderer->json($response, ['message' => 'Inventory item created.'])->withStatus(StatusCodeInterface::STATUS_CREATED);
  }
}
