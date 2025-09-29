<?php

namespace App\Domain\Document\Utilities;

use App\Domain\Document\Data\DocumentItem;
use App\Domain\Document\Repository\DocumentRepository;
final class DocumentUtils
{
  private DocumentRepository $repository;
  public function __construct(DocumentRepository $repository)
  {
    $this->repository = $repository;
  }

  public function transform(array $row): DocumentItem
  {
    $document = new DocumentItem();
    $document->uuid = $row['uuid'];
    $document->document_code = $row['document_code'];
    $document->status = $row['status'];
    $document->payment_status = $row['payment_status'];
    $document->owner = null;
    $document->signatures = null;
    $document->file = null;
    $document->owner_type = $row['owner_type'];
    $document->is_deleted = $row['is_deleted'];
    $document->created_at = $row['created_at'];
    $document->signed_at = $row['signed_at'];
    $document->deleted_at = $row['deleted_at'];
    return $document;
  }
}
