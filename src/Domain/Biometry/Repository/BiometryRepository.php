<?php

namespace App\Domain\Biometry\Repository;

use App\Domain\Biometry\Data\BiometryItem;
use App\Factory\PdoFactory;
use App\Database\PdoConnection;


final class BiometryRepository
{
  private PdoFactory $pdoFactory;
  private PdoConnection $pdo;

  public function __construct(PdoFactory $pdoFactory, PdoConnection $pdo)
  {
    $this->pdoFactory = $pdoFactory;
    $this->pdo = $pdo;
  }

  public function save(BiometryItem $biometry): void
  {
    $this->pdoFactory->create('biometric_history', $this->toRow($biometry));
  }

  public function toRow(BiometryItem $biometry): array
  {
    $row = [
      'document_id' => $biometry->document_id,
      'signer_id' => $biometry->signer_id,
      'verification_code' => $biometry->verification_code,
    ];
    return $row;
  }

  public function getAll(): array
  {
    return $this->pdoFactory->all('biometric_history');
  }

  public function getById(int $id): ?array
  {
    $biometry = $this->pdoFactory->find('biometric_history', $id);
    if (!$biometry) {
      return null;
    }

    return $biometry;
  }

  public function deleteByDocumentId(int $documentId): void
  {
    $this->pdoFactory->deleteBy('biometric_history', 'document_id', $documentId);
  }

  public function updateByConditions(array $conditions, array $data): void
  {
    $this->pdoFactory->updateByConditions('biometric_history', $conditions, $data);
  }

  public function checkAllValidationFinishedByDocumentId(int $documentId): bool
  {
    $biometricValidations = $this->pdoFactory->findAllBy('biometric_history', 'document_id', $documentId);

    $flag = true;
    foreach ($biometricValidations as $validation) {
      if (!$validation['is_done']) {
        $flag = false;
      }
    }

    return $flag;
  }

  public function getBySignatureId(int $signatureId): array
  {
    try {
      $sql = $this->pdo->query("SELECT * FROM biometric_history WHERE signer_id = $signatureId");
      $response = $sql->fetch(\PDO::FETCH_ASSOC);
      return $response;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }
}
