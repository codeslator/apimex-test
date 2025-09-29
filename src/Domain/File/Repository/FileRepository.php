<?php

namespace App\Domain\File\Repository;

use App\Domain\File\Data\FileItem;
use App\Factory\PdoFactory;
use App\Database\PdoConnection;

final class FileRepository
{
  private PdoFactory $pdoFactory;
  public PdoConnection $pdo;

  public function __construct(PdoFactory $pdoFactory, PdoConnection $pdo)
  {
    $this->pdoFactory = $pdoFactory;
    $this->pdo = $pdo;
  }

  public function save(FileItem $file): void
  {
    $this->pdoFactory->create('files', $this->toRow($file));
  }

  public function toRow(FileItem $file): array
  {
    $row = [
      'code' => $file->code,
      'name' => $file->name,
      'url' => $file->url,
      'remote_url' => $file->remote_url,
      'document_id' => $file->document_id,
      'signer_id' => $file->signer_id,
    ];
    return $row;
  }

  public function getAll(): array
  {
    return $this->pdoFactory->all('files');
  }

  public function getById(int $id): array
  {
    $file = $this->pdoFactory->find('files', $id);
    if (!$file) {
      throw new \DomainException(sprintf('File not found: %s', $id));
    }
    return $file;
  }

  public function getByDocumentId(int $documentId): array
  {
    try {
      $sql = $this->pdo->query("SELECT * FROM files WHERE document_id = $documentId");
      $file = $sql->fetch(\PDO::FETCH_ASSOC);
      return $file;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getByFileName(string $name): array
  {
    try {
      $sql = $this->pdo->query("SELECT * FROM files WHERE name = $name");
      $file = $sql->fetch(\PDO::FETCH_ASSOC);
      return $file;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getByDocumentAndSignerId(int $documentId, int $signerId): array
  {
    try {
      $sql = $this->pdo->query("SELECT * FROM files WHERE document_id = $documentId AND signer_id = $signerId");
      $file = $sql->fetchAll(\PDO::FETCH_ASSOC);
      return $file;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function updateByFileName(string $fileName, array $data): void
  {
    $this->pdoFactory->updateBy('files', 'name', $fileName, $data);
  }

  public function delete(int $id): void
  {
    $this->pdoFactory->delete('files', $id);
  }

  public function deleteByConditions(array $conditions): void
  {
    $this->pdoFactory->deleteByCondition('files', $conditions);
  }

  public function deleteByDocumentId(int $documentId): void
  {
    $this->pdoFactory->deleteBy('files', 'document_id', $documentId);
  }
}
