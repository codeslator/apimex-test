<?php

namespace App\Action\Payment;

use App\Domain\Payment\Service\PaymentService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PaymentWebhookPaymentAction
{
  private JsonRenderer $renderer;

  private PaymentService $service;

  public function __construct(PaymentService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $webhook = $this->service->webhook($data);
    return $this->renderer->json($response, $webhook)->withStatus(StatusCodeInterface::STATUS_OK);
  }
}
