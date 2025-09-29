<?php

namespace App\Domain\File\Utilities;

use App\Domain\File\Data\FileItem;
final class FileUtils
{
  public function __construct() {}

  public function transform(array $row): FileItem
  {
    $file = new FileItem();
    $file->id = $row['id'];
    $file->document_id = $row['document_id'];
    $file->signer_id = $row['signer_id'];
    $file->code = $row['code'];
    $file->name = $row['name'];
    $file->url = $row['url'];
    $file->remote_url = $row['remote_url'];
    $file->created_at = $row['created_at'];
    return $file;
  }
}