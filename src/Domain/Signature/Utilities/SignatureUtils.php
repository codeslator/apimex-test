<?php

namespace App\Domain\Signature\Utilities;

use App\Domain\Signature\Data\SignatureItem;

final class SignatureUtils
{
  public function __construct() {}

  public function transform(array $row): SignatureItem
  {
    $signature = new SignatureItem();
    $signature->id = $row['id'];
    $signature->uuid = $row['uuid'];
    $signature->document_id = $row['document_id'];
    $signature->signature_code = $row['signature_code'];
    $signature->rfc = $row['rfc'];
    $signature->curp = $row['curp'];
    $signature->first_name = $row['first_name'];
    $signature->last_name = $row['last_name'];
    $signature->mother_last_name = $row['mother_last_name'];
    $signature->birth_date = $row['birth_date'];
    $signature->email = $row['email'];
    $signature->role = $row['role'];
    $signature->portion = $row['portion'];
    $signature->payment = $row['payment'];
    $signature->iva_pay = $row['iva_pay'];
    $signature->total_pay = $row['total_pay'];
    $signature->is_paid = $row['is_paid'];
    $signature->is_prepaid = $row['is_prepaid'];
    $signature->is_signed = $row['is_signed'];
    $signature->signature_page = $row['signature_page'];
    $signature->posX = $row['posX'];
    $signature->posY = $row['posY'];
    $signature->require_video = $row['require_video'];
    return $signature;
  }
}
