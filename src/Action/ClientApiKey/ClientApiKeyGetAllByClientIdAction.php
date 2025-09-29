<?php

namespace App\Action\ClientApiKey;

use App\Domain\ClientApiKey\Service\ClientApiKeyService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Fig\Http\Message\StatusCodeInterface;

final class ClientApiKeyGetAllByClientIdAction
{
  private JsonRenderer $renderer;
  private ClientApiKeyService $service;

  public function __construct(ClientApiKeyService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    $clientId = $args['client_id'];
    $apikeys = $this->service->getByClientId((int) $clientId);
    return $this->renderer->response($response, $apikeys)->withStatus(StatusCodeInterface::STATUS_OK);
  }
}
