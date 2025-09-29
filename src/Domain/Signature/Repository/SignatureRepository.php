<?php

namespace App\Domain\Signature\Repository;

use App\Domain\Signature\Data\SignatureItem;
use App\Domain\Signature\Data\SignatureRole;
use App\Domain\Document\Data\DocumentOwnerType;
use App\Factory\PdoFactory;
use App\Database\PdoConnection;

final class SignatureRepository
{
  private PdoFactory $pdoFactory;
  public PdoConnection $pdo;


  public function __construct(PdoFactory $pdoFactory, PdoConnection $pdoConnection)
  {
    $this->pdoFactory = $pdoFactory;
    $this->pdo = $pdoConnection;
  }

  public function save(SignatureItem $signature): string
  {
    return $this->pdoFactory->create('signers', $this->toRow($signature));
  }

  public function toRow(SignatureItem $signature): array
  {
    $row = [
      'uuid' => $signature->uuid,
      'document_id' => $signature->document_id,
      'signature_code' => $signature->signature_code,
      'rfc' => $signature->rfc,
      'first_name' => $signature->first_name,
      'last_name' => $signature->last_name,
      'mother_last_name' => $signature->mother_last_name,
      'email' => $signature->email,
      'role' => SignatureRole::from($signature->role)->value,
      'signer_type' => DocumentOwnerType::from($signature->signer_type)->value,
      'portion' => $signature->portion,
      'payment' => $signature->payment,
      'iva_pay' => $signature->iva_pay,
      'total_pay' => $signature->total_pay,
      'is_paid' => (int)$signature->is_paid,
      'is_prepaid' => (int)$signature->is_prepaid,
      'is_signed' => (int)$signature->is_signed,
      'require_video' => ($signature->require_video !== null) ? (int)$signature->require_video : null,
      'signature_page' => $signature->signature_page,
      'posX' => $signature->posX,
      'posY' => $signature->posY,
    ];
    return $row;
  }

  public function getAll(): array
  {
    return $this->pdoFactory->all('signers');
  }

  public function getById(int $id): array
  {
    $document = $this->pdoFactory->find('signers', $id);
    if (!$document) {
      throw new \DomainException(sprintf('Signature not found: %s', $id));
    }

    return $document;
  }

  public function getAllByDocumentId(int $documentId): array
  {
    try {
      $sql = $this->pdo->query("SELECT * FROM signers WHERE document_id = $documentId AND role != 'PAYER'");
      $response = $sql->fetchAll(\PDO::FETCH_ASSOC);
      return $response;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getAllByDocumentIdAndRole(int $documentId, array $roles): array
  {
    try {
      $sql = $this->pdo->query("SELECT * FROM signers WHERE document_id = $documentId AND role IN ('".implode("','",$roles)."')");
      $response = $sql->fetchAll(\PDO::FETCH_ASSOC);
      return $response;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getByDocumentAndSignatureIds(int $signatureId, int $documentId): array
  {
    try {
      $sql = $this->pdo->query("SELECT * FROM signers WHERE id = $signatureId AND document_id = $documentId");
      $response = $sql->fetch(\PDO::FETCH_ASSOC);
      return $response;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function deleteByDocumentId(int $documentId): void
  {
    $this->pdoFactory->deleteBy('signers', 'document_id', $documentId);
  }

  public function updateById(int $id, $data): void
  {
    $this->pdoFactory->updateBy('signers', 'id', $id, $data);
  }

  public function updateByConditions(array $conditions, array $data): void
  {
    $this->pdoFactory->updateByConditions('signers', $conditions, $data);
  }

  public function deleteByConditions(array $conditions, array $data): void
  {
    $this->pdoFactory->updateByConditions('signers', $conditions, $data);
  }
}
