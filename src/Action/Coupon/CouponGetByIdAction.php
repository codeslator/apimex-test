<?php

namespace App\Action\Coupon;

use App\Domain\Coupon\Service\CouponService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
final class CouponGetByIdAction
{
  private JsonRenderer $renderer;
  private CouponService $service;

  public function __construct(CouponService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    try {
      $id = (int)$args['id'];
      $coupons = $this->service->getById($id);
      return $this->renderer->response($response, $coupons);
    } catch (\PDOException $e) {
      return $this->renderer->json($response, false);

    }
  }

}
