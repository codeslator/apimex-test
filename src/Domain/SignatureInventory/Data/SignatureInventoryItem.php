<?php
namespace App\Domain\SignatureInventory\Data;

final class SignatureInventoryItem {
  public int $id;
  public int $quantity;
  public string $source;
  public string $created_at;

}