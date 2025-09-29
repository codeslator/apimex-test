<?php
namespace App\Domain\SignatureCredit\Data;

final class SignatureCreditItem {
  public int $id;
  public int $consumed_quantity;
  public int $remaining_quantity;
  public ?string $from_date;
  public ?string $to_date;
  public string $created_at;
}