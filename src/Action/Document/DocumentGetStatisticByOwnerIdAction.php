<?php

namespace App\Action\Document;

use App\Domain\Document\Service\DocumentService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
final class DocumentGetStatisticByOwnerIdAction
{
  private JsonRenderer $renderer;
  private DocumentService $service;

  public function __construct(DocumentService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    $id = $args['id'];
    $document = $this->service->getStatisticByOwnerId($id);
    return $this->renderer->response($response, $document);
  }

}
