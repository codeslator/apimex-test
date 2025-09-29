<?php

namespace App\Action\ClientApiKey;

use App\Domain\ClientApiKey\Service\ClientApiKeyService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Fig\Http\Message\StatusCodeInterface;

final class ClientApiKeyDeactivateByIdAction
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
    $id = $args['id'];
    $this->service->deactivateKeyById($id);
    return $this->renderer->json($response, ['message' => "ApiKey with id ($id) have been deactivated."])->withStatus(StatusCodeInterface::STATUS_OK);
  }
}
