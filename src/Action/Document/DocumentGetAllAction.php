<?php

namespace App\Action\Document;

use App\Domain\Document\Service\DocumentService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Fig\Http\Message\StatusCodeInterface;

final class DocumentGetAllAction
{
  private JsonRenderer $renderer;
  private DocumentService $service;

  public function __construct(DocumentService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $pagination = $request->getQueryParams();
    $documents = $this->service->getAll($pagination);
    return $this->renderer->json($response, $documents)->withStatus(StatusCodeInterface::STATUS_OK);
  }
}
