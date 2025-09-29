<?php

namespace App\Action\DocumentTypeFee;

use App\Domain\DocumentTypeFee\Service\DocumentTypeFeeService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
final class DocumentTypeFeeGetByIdAction
{
  private JsonRenderer $renderer;
  private DocumentTypeFeeService $service;

  public function __construct(DocumentTypeFeeService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    $id = (int)$args['id'];
    $documentTypeFee = $this->service->getById($id);
    return $this->renderer->response($response, $documentTypeFee)->withStatus(StatusCodeInterface::STATUS_OK);
  }

}
