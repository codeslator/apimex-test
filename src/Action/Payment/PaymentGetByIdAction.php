<?php

namespace App\Action\Payment;

use App\Domain\Payment\Service\PaymentService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
final class PaymentGetByIdAction
{
  private JsonRenderer $renderer;
  private PaymentService $service;

  public function __construct(PaymentService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    $id = (int)$args['id'];
    $payment = $this->service->getById($id);
    return $this->renderer->response($response, $payment);
  }

}
