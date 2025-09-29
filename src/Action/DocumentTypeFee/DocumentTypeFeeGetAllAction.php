<?php

namespace App\Action\DocumentTypeFee;

use App\Domain\DocumentTypeFee\Service\DocumentTypeFeeService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DocumentTypeFeeGetAllAction
{
  private JsonRenderer $renderer;
  private DocumentTypeFeeService $service;

  public function __construct(DocumentTypeFeeService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $documentTypeFees = $this->service->getAll();
    return $this->renderer->response($response, $documentTypeFees);
  }
}
