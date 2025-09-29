<?php

namespace App\Action\SignatureCredit;

use App\Domain\SignatureCredit\Service\SignatureCreditService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SignatureCreditCreateAction
{
  private JsonRenderer $renderer;

  private SignatureCreditService $service;

  public function __construct(SignatureCreditService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $this->service->create($data);
    return $this->renderer->json($response, ['message' => 'User signature credit item created.'])->withStatus(StatusCodeInterface::STATUS_CREATED);
  }
}
