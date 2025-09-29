<?php

namespace App\Action\Client;

use App\Domain\Client\Service\ClientService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ClientCreateAction
{
  private JsonRenderer $renderer;

  private ClientService $service;

  public function __construct(ClientService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $this->service->create($data);
    return $this->renderer->json($response, ['message' => 'Client created.'])->withStatus(StatusCodeInterface::STATUS_CREATED);
  }
}
