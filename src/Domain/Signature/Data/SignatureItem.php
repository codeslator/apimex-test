<?php
namespace App\Domain\Signature\Data;
use App\Domain\Biometry\Data\BiometryItem;
use App\Domain\Payment\Data\PaymentItem;

final class SignatureItem {
  public int $id;
  public string $uuid;
  public int $document_id;
  public string $signature_code ;
  public bool $is_signed;
  public string $rfc;
  public mixed $curp;
  public string $first_name;
  public string $last_name;
  public string $mother_last_name;
  public mixed $birth_date;
  public string $email;
  public string $role;
  public string $signer_type;
  public int $portion;
  public float $payment;
  public float $iva_pay;
  public float $total_pay;
  public bool $is_paid;
  public bool $is_prepaid;
  public ?bool $require_video;
  public int $signature_page;
  public int $posX;
  public int $posY;
  public mixed $created_at;
  public mixed $signed_at;
  public ?BiometryItem $biometric_validation;
  public ?PaymentItem $payment_data;
}