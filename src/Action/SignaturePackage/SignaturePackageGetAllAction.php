<?php

namespace App\Action\SignaturePackage;

use App\Domain\SignaturePackage\Service\SignaturePackageService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SignaturePackageGetAllAction
{
  private JsonRenderer $renderer;
  private SignaturePackageService $service;

  public function __construct(SignaturePackageService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $packages = $this->service->getAll();
    return $this->renderer->response($response, $packages);
  }
}
