<?php

namespace App\Action\SignaturePackage;

use App\Domain\SignaturePackage\Service\SignaturePackageService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
final class SignaturePackageGetByIdAction
{
  private JsonRenderer $renderer;
  private SignaturePackageService $service;

  public function __construct(SignaturePackageService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    try {
      $id = (int)$args['id'];
      $package = $this->service->getById($id);
      return $this->renderer->response($response, $package);
    } catch (\PDOException $e) {
      return $this->renderer->json($response, false);

    }
  }

}
