<?php
namespace App\Domain\File\Data;

use App\Domain\Document\Data\DocumentItem;

final class FileItem {
  public int $id;
  public mixed $code;
  public string $name;
  public string $url;
  public string $remote_url;
  public int $document_id;
  public ?int $signer_id;
  public string $created_at;
  public ?DocumentItem $document;

}