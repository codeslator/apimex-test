<?php

namespace App\Domain\DocumentType\Service;

use App\Domain\DocumentType\Repository\DocumentTypeRepository;
use App\Domain\DocumentType\Data\DocumentTypeItem;

final class DocumentTypeService
{

  private DocumentTypeRepository $repository;

  public function __construct(DocumentTypeRepository $repository)
  {
    $this->repository = $repository;
  }

  public function getAll(): array
  {
    return $this->repository->getAll();
  }

  public function create(array $data): void
  {
    $documentType = new DocumentTypeItem();
    $documentType->name = $data['name'];
    $documentType->document_type = $data['document_type'];
    $this->repository->save($documentType);
  }

  public function getById(int $id): DocumentTypeItem
  {
    return $this->transform($this->repository->getById($id));
  }

  public function transform(array $row): DocumentTypeItem
  {
    $documentType = new DocumentTypeItem();
    $documentType->id = $row['id'];
    $documentType->name = $row['name'];
    $documentType->document_type = $row['document_type'];
    return $documentType;
  }
}
