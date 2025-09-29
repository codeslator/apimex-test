<?php

namespace App\Domain\DocumentTypeFee\Repository;

use App\Domain\DocumentTypeFee\Data\DocumentTypeFeeItem;
use App\Domain\DocumentTypeFee\Data\DocumentTypeFeeModality;
use App\Domain\DocumentType\Data\DocumentTypeItem;
use App\Database\PdoConnection;
use App\Factory\PdoFactory;

final class DocumentTypeFeeRepository
{
  private PdoFactory $pdoFactory;
  public PdoConnection $pdo;

  public function __construct(PdoFactory $pdoFactory, PdoConnection $pdoConnection)
  {
    $this->pdoFactory = $pdoFactory;
    $this->pdo = $pdoConnection;
  }

  public function getAll(): array
  {
    try {
      $documentTypeFees = [];
      $sql = $this->pdo->query("SELECT * FROM document_type_fee");
      $response = $sql->fetchAll(\PDO::FETCH_ASSOC);
      foreach ($response as $row) {
        $documentTypeFee = new DocumentTypeFeeItem();
        $documentTypeFee->id = $row['id'];
        $documentTypeFee->modality = $row['modality'];
        $documentTypeFee->sign_count = $row['sign_count'];
        $documentTypeFee->amount = $row['amount'];
        $documentTypeFee->amount_iva = $row['amount_iva'];
        $documentTypeFee->iva = $row['iva'];
        $documentTypeFee->total = $row['total'];
        $documentTypeFee->is_active = $row['is_active'];
        $documentTypeFee->document_type = $this->getDocumentType($row['document_type_id']);
        $documentTypeFees[] = $documentTypeFee;
      }
      return $documentTypeFees;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getDocumentType(int $documentTypeId): DocumentTypeItem
  {
    try {
      $sql = $this->pdo->query("SELECT * FROM document_type WHERE id = $documentTypeId");
      $response = $sql->fetch(\PDO::FETCH_ASSOC);
      $documentType = new DocumentTypeItem();
      $documentType->id = $response['id'];
      $documentType->name = $response['name'];
      $documentType->document_type = $response['document_type'];
      return $documentType;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getById(int $id): array
  {
    $documentTypeFee = $this->pdoFactory->find('document_type_fee', $id);
    if (!$documentTypeFee) {
      throw new \DomainException(sprintf('Document Type not found: %s', $id));
    }

    return $documentTypeFee;
  }

  public function save(DocumentTypeFeeItem $documentTypeFee): void
  {
    $this->pdoFactory->create('document_type_fee', $this->toRow($documentTypeFee));
  }

  public function toRow(DocumentTypeFeeItem $documentTypeFee): array
  {
    $row = [
      'modality' => DocumentTypeFeeModality::from($documentTypeFee->modality)->value,
      'sign_count' => $documentTypeFee->sign_count,
      'amount' => $documentTypeFee->amount,
      'amount_iva' => $documentTypeFee->amount_iva,
      'iva' => $documentTypeFee->iva,
      'total' => $documentTypeFee->total,
      'is_active' => $documentTypeFee->is_active,
      'document_type_id' => $documentTypeFee->document_type_id,
    ];
    return $row;
  }
}
