<?php
namespace App\Domain\Coupon\Data;

final class CouponItem {
  public int $id;
  public string $code;
  public float $discount_amount;
  public string $expiration_date;
  public string $created_at;

}