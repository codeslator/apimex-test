<?php

namespace App\Action\SignaturePackagePurchase;

use App\Domain\SignaturePackagePurchase\Service\SignaturePackagePurchaseService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SignaturePackagePurchaseCreatePurchase
{
  private JsonRenderer $renderer;

  private SignaturePackagePurchaseService $service;

  public function __construct(SignaturePackagePurchaseService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $loggedUser = $request->getAttribute('user');
    $this->service->createFromPurchase($data, $loggedUser);
    return $this->renderer->json($response, ['message' => 'Package purchase created successfully.'])->withStatus(StatusCodeInterface::STATUS_OK);
  }
}
