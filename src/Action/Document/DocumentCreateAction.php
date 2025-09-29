<?php

namespace App\Action\Document;

use App\Domain\Document\Service\DocumentService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DocumentCreateAction
{
  private JsonRenderer $renderer;

  private DocumentService $service;

  public function __construct(DocumentService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $loggedUser = $request->getAttribute('user');
    $documentId = $this->service->create($data, $loggedUser);
    return $this->renderer->json($response, data: ['message' => 'Document created.', 'document_id' => $documentId])->withStatus(StatusCodeInterface::STATUS_CREATED);
  }
}
