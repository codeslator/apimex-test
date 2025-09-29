<?php

namespace App\Action\SignatureInventory;

use App\Domain\SignatureInventory\Service\SignatureInventoryService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SignatureInventoryUpdateAction
{
  private JsonRenderer $renderer;

  private SignatureInventoryService $service;

  public function __construct(SignatureInventoryService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $id = (int)$args["id"];
    $this->service->updateById($id, $data);
    return $this->renderer->json($response, ['message' => 'Inventory updated successfully.'])->withStatus(StatusCodeInterface::STATUS_OK);
  }
}
