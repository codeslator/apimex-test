<?php
namespace App\Domain\Payment\Data;

use App\Domain\User\Data\UserItem;

final class PaymentItem {
  public int $id;
  public ?int $signer_id;
  public ?int $purchase_id;
  public ?int $payer_id;
  public ?UserItem $payer;
  public string $payment_link;
  public string $invoice_id;
  public float $amount;
  public mixed $status;
  public string $method_type;
  public ?string $info_link_creator;
  public ?string $response_payment_link;
  public ?string $completed_at;
  public string $created_at;
}