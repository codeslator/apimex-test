<?php

namespace App\Action\DocumentTypeFee;

use App\Domain\DocumentTypeFee\Service\DocumentTypeFeeService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DocumentTypeFeeCreateAction
{
  private JsonRenderer $renderer;

  private DocumentTypeFeeService $service;

  public function __construct(DocumentTypeFeeService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $this->service->create($data);
    return $this->renderer->json($response, ['message' => 'Document type fee created.'])->withStatus(StatusCodeInterface::STATUS_CREATED);
  }
}
