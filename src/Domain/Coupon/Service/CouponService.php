<?php

namespace App\Domain\Coupon\Service;

use App\Domain\Coupon\Repository\CouponRepository;
use App\Domain\Coupon\Data\CouponItem;

final class CouponService
{

  private CouponRepository $repository;

  public function __construct(CouponRepository $repository)
  {
    $this->repository = $repository;
  }

  public function getAll(): array
  {
    $rows = [];
    foreach ($this->repository->getAll() as $row) {
      $rows[] = $this->transform($row);
    }
    return $rows;
  }

  public function create(array $data): void
  {
    $coupon = new CouponItem();
    $coupon->code = $data['code'];
    $coupon->discount_amount = $data['discount_amount'];
    $coupon->expiration_date = $data['expiration_date'];
    $this->repository->save($coupon);
  }

  public function getById(int $id): CouponItem
  {
    return $this->transform($this->repository->getById($id));
  }

  public function transform(array $row): CouponItem
  {
    $coupon = new CouponItem();
    $coupon->id = (int)$row['id'];
    $coupon->code = $row['code'];
    $coupon->discount_amount = (float)$row['discount_amount'];
    $coupon->expiration_date = $row['expiration_date'];
    $coupon->created_at = $row['created_at'];
    return $coupon;
  }

  public function updateById(int $id, array $data): void
  {
    $this->repository->updateById($id, $data);
  }

  public function deleteById(int $id): void
  {
    $this->repository->deleteById($id);
  }
}
