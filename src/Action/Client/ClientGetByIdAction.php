<?php

namespace App\Action\Client;

use App\Domain\Client\Service\ClientService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
final class ClientGetByIdAction
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
    $client = $this->service->getById($id);
    return $this->renderer->response($response, $client);
  }

}
