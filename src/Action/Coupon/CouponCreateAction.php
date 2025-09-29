<?php

namespace App\Action\Coupon;

use App\Domain\Coupon\Service\CouponService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CouponCreateAction
{
  private JsonRenderer $renderer;

  private CouponService $service;

  public function __construct(CouponService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $this->service->create($data);
    return $this->renderer->json($response, ['message' => 'Coupon created.'])->withStatus(StatusCodeInterface::STATUS_CREATED);
  }
}
