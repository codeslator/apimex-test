<?php

namespace App\Action\Client;

use App\Domain\Client\Service\ClientService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Fig\Http\Message\StatusCodeInterface;

final class ClientGetAllAction
{
  private JsonRenderer $renderer;
  private ClientService $service;

  public function __construct(ClientService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $pagination = $request->getQueryParams();
    $clients = $this->service->getAll($pagination);
    return $this->renderer->json($response, $clients)->withStatus(StatusCodeInterface::STATUS_OK);
  }
}
