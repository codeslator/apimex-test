<?php

namespace App\Action\Client;

use App\Domain\Client\Service\ClientService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
final class ClientDeactivateByIdAction
{
  private JsonRenderer $renderer;
  private ClientService $service;

  public function __construct(ClientService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    $id = $args['id'];
    $this->service->deactivateById($id);
    return $this->renderer->response($response, ['message' => 'Client and associated API keys have been deactivated.'], 200);
  }

}
