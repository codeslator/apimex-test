<?php

namespace App\Action\SignaturePackagePurchase;

use App\Domain\SignaturePackagePurchase\Service\SignaturePackagePurchaseService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SignaturePackagePurchaseGetAllByOwnerIdAction
{
  private JsonRenderer $renderer;
  private SignaturePackagePurchaseService $service;

  public function __construct(SignaturePackagePurchaseService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    $userId = (int)$args['user_id'];
    $purchases = $this->service->getAllByUserId($userId);
    return $this->renderer->response($response, $purchases);
  }
}
