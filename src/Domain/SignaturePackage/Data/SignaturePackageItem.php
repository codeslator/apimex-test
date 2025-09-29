<?php
namespace App\Domain\SignaturePackage\Data;

final class SignaturePackageItem {
  public int $id;
  public string $uuid;
  public string $name;
  public float $price_per_signature;
  public float $iva;
  public int $min_quantity;
  public int $max_quantity;
  public string $created_at;

}