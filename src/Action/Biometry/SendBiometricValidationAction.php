<?php

namespace App\Action\Biometry;

use App\Domain\Biometry\Service\BiometryService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SendBiometricValidationAction
{
  private JsonRenderer $renderer;

  private BiometryService $service;

  public function __construct(BiometryService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $this->service->sendBiometricValidation($data);
    return $this->renderer->json($response, ['message' => 'Files uploaded successfully.'])->withStatus(StatusCodeInterface::STATUS_CREATED);
  }
}
