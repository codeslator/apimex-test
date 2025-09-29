<?php

namespace App\Action\Payment;

use App\Domain\Payment\Service\PaymentService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Fig\Http\Message\StatusCodeInterface;

final class PaymentGetAllAction
{
  private JsonRenderer $renderer;
  private PaymentService $service;

  public function __construct(PaymentService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $pagination = $request->getQueryParams();
    $payments = $this->service->getAll($pagination);
    return $this->renderer->json($response, $payments)->withStatus(StatusCodeInterface::STATUS_OK);
  }
}
