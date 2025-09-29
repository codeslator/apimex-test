<?php

namespace App\Action\SignaturePackage;

use App\Domain\SignaturePackage\Service\SignaturePackageService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SignaturePackageCreateAction
{
  private JsonRenderer $renderer;

  private SignaturePackageService $service;

  public function __construct(SignaturePackageService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $this->service->create($data);
    return $this->renderer->json($response, ['message' => 'Package created.'])->withStatus(StatusCodeInterface::STATUS_CREATED);
  }
}
