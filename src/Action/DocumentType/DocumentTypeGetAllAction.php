<?php

namespace App\Action\DocumentType;

use App\Domain\DocumentType\Service\DocumentTypeService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DocumentTypeGetAllAction
{
  private JsonRenderer $renderer;
  private DocumentTypeService $service;

  public function __construct(DocumentTypeService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $documentTypes = $this->service->getAll();
    return $this->renderer->response($response, $documentTypes);
  }
}
