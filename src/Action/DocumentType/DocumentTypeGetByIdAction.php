<?php

namespace App\Action\DocumentType;

use App\Domain\DocumentType\Service\DocumentTypeService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
final class DocumentTypeGetByIdAction
{
  private JsonRenderer $renderer;
  private DocumentTypeService $service;

  public function __construct(DocumentTypeService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    $id = (int)$args['id'];
    $documentType = $this->service->getById($id);
    return $this->renderer->response($response, $documentType)->withStatus(StatusCodeInterface::STATUS_OK);
  }

}
