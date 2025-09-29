<?php

namespace App\Domain\Document\Repository;

use App\Domain\Document\Data\DocumentItem;
use App\Factory\PdoFactory;
use App\Database\PdoConnection;
use App\Traits\PaginateTrait;
use App\Factory\Pagination\PageRequest;

final class DocumentRepository
{
  use PaginateTrait;
  private PdoFactory $pdoFactory;
  public PdoConnection $pdo;

  public function __construct(PdoFactory $pdoFactory, PdoConnection $pdo)
  {
    $this->pdoFactory = $pdoFactory;
    $this->pdo = $pdo;
  }

  public function save(DocumentItem $document): string
  {
    try {
      $this->pdo->beginTransaction();
      $id = $this->pdoFactory->create('documents', $this->toRow($document));
      $documentCode = str_pad($id, 3, "0", STR_PAD_LEFT);
      $this->pdoFactory->update('documents', $id, ["document_code" => "APMX$documentCode"]);
      $this->pdo->commit();
      return $id;
    } catch (\Exception $e) {
      $this->pdo->rollBack();
      throw new \DomainException($e->getMessage());
    }
  }

  public function toRow(DocumentItem $document): array
  {
    $row = [
      'uuid' => $document->uuid,
      'status' => $document->status,
      'document_type_fee_id' => $document->document_type_fee_id,
      'payment_status' => $document->payment_status,
      'owner_id' => $document->owner_id,
      'owner_type' => $document->owner_type,
      'signer_count' => $document->signer_count,
    ];
    return $row;
  }

  public function getAll(array $pagination): array
  {
    $pageRequest = PageRequest::of(
      $pagination['page'] ?? 1,
      $pagination['per_page'] ?? 10,
      $pagination['sort_by'] ?? 'id',
      $pagination['sort_order'] ?? 'DESC',
    );
    $startDate = isset($pagination['start_date']) ? $pagination['start_date'] : null;
    $endDate = isset($pagination['end_date']) ? $pagination['end_date'] : null;
    $status = isset($pagination['status']) ? $pagination['status'] : null;
    $paymentStatus = isset($pagination['payment_status']) ? $pagination['payment_status'] : null;
    $ownerId = isset($pagination['owner_id']) ? $pagination['owner_id'] : null;
    $search = isset($pagination['search']) ? $pagination['search'] : null;
    $query = "SELECT d.* FROM documents d LEFT JOIN files f ON f.name = CONCAT(d.uuid, '.pdf') LEFT JOIN users u ON u.id = d.owner_id 
              WHERE 
              (DATE(d.created_at) >= NULLIF('{$startDate}', '') OR NULLIF('{$startDate}', '') IS NULL)
              AND (DATE(d.created_at) <= NULLIF('{$endDate}', '') OR NULLIF('{$endDate}', '') IS NULL)
              AND (d.status = NULLIF('{$status}', '') OR NULLIF('{$status}', '') IS NULL)
              AND (d.payment_status = NULLIF('{$paymentStatus}', '') OR NULLIF('{$paymentStatus}', '') IS NULL)
              AND (d.owner_id = NULLIF('{$ownerId}', '') OR NULLIF('{$ownerId}', '') IS NULL)
              AND (
                d.document_code LIKE '%{$search}%' OR 
                f.name LIKE '%{$search}%' OR
                NULLIF('{$search}', '') IS NULL
              )";
    $data = $this->paginateByQuery($query, $pageRequest);
    return $data->toArray();
  }

  public function getAllByOwnerId(int $ownerId, array $pagination): array
  {
    $pageRequest = PageRequest::of(
      $pagination['page'] ?? 1,
      $pagination['per_page'] ?? 10,
      $pagination['sort_by'] ?? 'id',
      $pagination['sort_order'] ?? 'DESC',
    );
    $startDate = isset($pagination['start_date']) ? $pagination['start_date'] : null;
    $endDate = isset($pagination['end_date']) ? $pagination['end_date'] : null;
    $status = isset($pagination['status']) ? $pagination['status'] : null;
    $search = isset($pagination['search']) ? $pagination['search'] : null;
    $query = "SELECT d.* FROM documents d LEFT JOIN files f ON f.name = CONCAT(d.uuid, '.pdf') LEFT JOIN users u ON u.id = d.owner_id 
              WHERE 
              (DATE(d.created_at) >= NULLIF('{$startDate}', '') OR NULLIF('{$startDate}', '') IS NULL)
              AND (DATE(d.created_at) <= NULLIF('{$endDate}', '') OR NULLIF('{$endDate}', '') IS NULL)
              AND (d.status = NULLIF('{$status}', '') OR NULLIF('{$status}', '') IS NULL)
              AND (d.owner_id = NULLIF('{$ownerId}', '') OR NULLIF('{$ownerId}', '') IS NULL)
              AND (
                d.document_code LIKE '%{$search}%' OR 
                f.name LIKE '%{$search}%' OR
                NULLIF('{$search}', '') IS NULL
              )";
    $data = $this->paginateByQuery($query, $pageRequest);
    return $data->toArray();
  }

  public function getById(int $id): ?array
  {
    $document = $this->pdoFactory->find('documents', $id);
    if (!$document) {
      return null;
    }
    return $document;
  }

  public function getByUuid(string $uuid): ?array
  {
    try {
      $sql = $this->pdo->query("SELECT * FROM documents WHERE uuid = '$uuid'");
      $response = $sql->fetch(\PDO::FETCH_ASSOC);
      if (!$response) {
        return null;
      }
      return $response;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function delete(int $id, array $data): void
  {
    // $this->pdoFactory->delete('documents', $id);
    $this->pdoFactory->update('documents', $id, $data);
  }

  public function update(int $id, array $data): void
  {
    $this->pdoFactory->update('documents', $id, $data);
  }

  public function getOwnerForDocument(int $ownerId): array
  {
    try {
      $sql = $this->pdo->query("SELECT u.* FROM users u INNER JOIN documents d ON d.owner_id = u.id WHERE d.owner_id = $ownerId");
      $response = $sql->fetch(\PDO::FETCH_ASSOC);
      return $response;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getSignaturesForDocument(int $documentId): array
  {
    try {
      $sql = $this->pdo->query("SELECT s.* FROM signers s INNER JOIN documents d ON d.id = s.document_id WHERE d.id = $documentId");
      $response = $sql->fetchAll(\PDO::FETCH_ASSOC);
      return $response;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getFileForDocument(int $documentId): array
  {
    try {
      $sql = $this->pdo->query("SELECT f.* FROM files f INNER JOIN documents d ON d.id = f.document_id WHERE d.id = $documentId");
      $response = $sql->fetch(\PDO::FETCH_ASSOC);
      return $response;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getBiometryBySignatureId(int $signatureId): mixed
  {
    try {
      $sql = $this->pdo->query("SELECT * FROM biometric_history WHERE signer_id = $signatureId");
      $response = $sql->fetch(\PDO::FETCH_ASSOC);
      return $response;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getPaymentBySignatureId(int $signatureId): array
  {
    try {
      $sql = $this->pdo->query("SELECT * FROM signer_payment WHERE signer_id = $signatureId");
      $response = $sql->fetch(\PDO::FETCH_ASSOC);
      return $response;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getDocumentTotalsByOwnerId(int $ownerId): array
  {
    try {
      $sql = $this->pdo->query("SELECT
                                COUNT(*) AS total,
                                SUM(CASE WHEN status = 'CREATED' THEN 1 ELSE 0 END) AS created,
                                SUM(CASE WHEN status = 'SIGNED_PENDING' THEN 1 ELSE 0 END) AS pending,
                                SUM(CASE WHEN status = 'SIGNED' THEN 1 ELSE 0 END) AS signed
                            FROM documents WHERE owner_id = $ownerId AND is_deleted = false");
      $response = $sql->fetch(\PDO::FETCH_ASSOC);
      return $response;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }
}
