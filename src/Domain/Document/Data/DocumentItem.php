<?php
namespace App\Domain\Document\Data;

use App\Domain\User\Data\UserItem;
use App\Domain\File\Data\FileItem;

final class DocumentItem {
  public int $id;
  public string $uuid;
  public string $document_code;
  public int $document_type_fee_id;
  public string $status;
  public string $payment_status;
  public string $owner_type;
  public int $owner_id;
  public int $signer_count;
  public ?array $signatures;
  public ?UserItem $owner;
  public ?FileItem $file;
  public ?bool $is_deleted;

  public mixed $created_at;
  public mixed $signed_at;
  public mixed $deleted_at;
}