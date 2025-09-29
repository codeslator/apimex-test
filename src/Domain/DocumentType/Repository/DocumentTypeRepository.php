<?php

namespace App\Domain\DocumentType\Repository;

use App\Domain\DocumentType\Data\DocumentTypeItem;
use App\Domain\DocumentType\Data\DocumentType;
use App\Factory\PdoFactory;

final class DocumentTypeRepository
{
  private PdoFactory $pdoFactory;

  public function __construct(PdoFactory $pdoFactory)
  {
    $this->pdoFactory = $pdoFactory;
  }

  public function save(DocumentTypeItem $documentType): void
  {
    $this->pdoFactory->create('document_type', $this->toRow($documentType));
  }

  public function toRow(DocumentTypeItem $documentType): array
  {
    $row = [
      'name' => $documentType->name,
      'document_type' => DocumentType::from($documentType->document_type)->value,
    ];
    return $row;
  }

  public function getAll(): array
  {
    return $this->pdoFactory->all('document_type');
  }

  public function getById(int $id): array
  {
    $documentType = $this->pdoFactory->find('document_type', $id);
    if (!$documentType) {
      throw new \DomainException(sprintf('Document Type not found: %s', $id));
    }

    return $documentType;
  }
}
