<?php
namespace App\Domain\SignaturePackagePurchase\Data;

final class SignaturePackagePurchaseItem {
  public int $id;
  public int $credit_id;
  public int $package_id;
  public int $quantity;
  public float $total_price;
  public float $total_iva;
  public float $amount;
  public bool $is_paid;
  public ?string $completed_at;
  public mixed $payment_data;
  public string $created_at;

}