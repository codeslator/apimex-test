<?php

namespace App\Action\ClientApiKey;

use App\Domain\ClientApiKey\Service\ClientApiKeyService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ClientApiKeyGenerateKeyAction
{
  private JsonRenderer $renderer;

  private ClientApiKeyService $service;

  public function __construct(ClientApiKeyService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $this->service->generateKey($data);
    return $this->renderer->response($response, ['message' => 'API Key generated successfully.'])->withStatus(StatusCodeInterface::STATUS_OK);
  }
}
