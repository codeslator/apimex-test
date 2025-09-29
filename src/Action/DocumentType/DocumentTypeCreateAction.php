<?php

namespace App\Action\DocumentType;

use App\Domain\DocumentType\Service\DocumentTypeService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DocumentTypeCreateAction
{
  private JsonRenderer $renderer;

  private DocumentTypeService $service;

  public function __construct(DocumentTypeService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $this->service->create($data);
    return $this->renderer->json($response, ['message' => 'Document type created.'])->withStatus(StatusCodeInterface::STATUS_CREATED);
  }
}
