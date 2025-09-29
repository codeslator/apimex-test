<?php
namespace App\Domain\DocumentTypeFee\Data;

use App\Domain\DocumentType\Data\DocumentTypeItem;

final class DocumentTypeFeeItem {
  public int $id;
  public string $modality;
  public int $sign_count;
  public float $amount;
  public float $amount_iva;
  public float $iva;
  public float $total;
  public bool $is_active;
  public int $document_type_id;
  public ?DocumentTypeItem $document_type;


}