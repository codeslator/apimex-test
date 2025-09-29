<?php

namespace App\Domain\Coupon\Repository;

use App\Domain\Coupon\Data\CouponItem;
use App\Factory\PdoFactory;

final class CouponRepository
{
  private PdoFactory $pdoFactory;

  public function __construct(PdoFactory $pdoFactory)
  {
    $this->pdoFactory = $pdoFactory;
  }

  public function save(CouponItem $coupon): void
  {
    $this->pdoFactory->create('coupons', $this->toRow($coupon));
  }

  public function toRow(CouponItem $coupon): array
  {
    $row = [
      'code' => $coupon->code,
      'discount_amount' => $coupon->discount_amount,
      'expiration_date' => $coupon->expiration_date,
    ];
    return $row;
  }

  public function getAll(): array
  {
    return $this->pdoFactory->all('coupons');
  }

  public function getById(int $id): array
  {
    $coupon = $this->pdoFactory->find('coupons', $id);
    if (!$coupon) {
      throw new \DomainException(sprintf('Item not found: %s', $id));
    }

    return $coupon;
  }

  public function updateById(int $id, array $data): void
  {
    $this->pdoFactory->update('coupons', $id, $data);
  }

  public function deleteById(int $id): void
  {
    $this->pdoFactory->delete('coupons', $id);
  }
}
