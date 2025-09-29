<?php

namespace App\Action\SignaturePackage;

use App\Domain\SignaturePackage\Service\SignaturePackageService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
final class SignaturePackageUpdateByIdAction
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
      $data = (array)$request->getParsedBody();
      $this->service->updateById($id, $data);
      return $this->renderer->json($response, ['message' => 'Package updated.'])->withStatus(StatusCodeInterface::STATUS_OK);
    } catch (\PDOException $e) {
      return $this->renderer->json($response, false);
    }
  }

}
