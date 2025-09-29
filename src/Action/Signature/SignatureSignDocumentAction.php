<?php

namespace App\Action\Signature;

use App\Domain\Signature\Service\SignatureService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SignatureSignDocumentAction
{
  private JsonRenderer $renderer;

  private SignatureService $service;

  public function __construct(SignatureService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $sign = $this->service->signDocument($data);
    $status = $sign == 'success' ? StatusCodeInterface::STATUS_OK : StatusCodeInterface::STATUS_BAD_REQUEST;
    $message = $sign == 'success' ? 'Document signed successfully.' : $sign;
    return $this->renderer->json($response, ['message' => $message])->withStatus($status);
  }
}
