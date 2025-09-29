<?php

namespace App\Domain\ClientApiKey\Repository;

use App\Domain\ClientApiKey\Data\ClientApiKeyItem;
use App\Factory\PdoFactory;
use App\Database\PdoConnection;

final class ClientApiKeyRepository
{
  private PdoFactory $pdoFactory;
  public PdoConnection $pdo;

  public function __construct(PdoFactory $pdoFactory, PdoConnection $pdo)
  {
    $this->pdoFactory = $pdoFactory;
    $this->pdo = $pdo;
  }

  public function findActiveByKey(string $key): mixed
  {
    return $this->pdoFactory->findActiveByKey($key);
  }

  public function createKey(int $clientId, string $key): void
  {
    $this->pdoFactory->createKey($clientId, $key);
  }

  public function deactivateAllByClient(int $clientId): void
  {
    $this->pdoFactory->deactivateAllByClient($clientId);
  }

  public function save(ClientApiKeyItem $apiKey): void
  {
    try {
      $this->pdo->beginTransaction();
      $this->pdoFactory->create('client_api_keys', $this->toRow($apiKey));
      $this->pdo->commit();
    } catch (\PDOException $e) {
      $this->pdo->rollBack();
      throw new \DomainException($e->getMessage());
    }
  }

  public function toRow(ClientApiKeyItem $apiKey): array
  {
    $row = [
      'client_id' => $apiKey->client_id,
      'api_key' => $apiKey->api_key,
      'name' => $apiKey->name,
      'description' => $apiKey->description,
      'environment' => $apiKey->environment,
      'rate_limit' => $apiKey->rate_limit,
      'rate_limit_window' => $apiKey->rate_limit_window,
    ];
    return $row;
  }

  public function getAll(array $pagination): array
  {
    $page = $pagination['page'] ?? 1;
    $perPage = $pagination['per_page'] ?? 10;
    $sortBy = $pagination['sort_by'] ?? 'id';
    $sortOrder = $pagination['sort_order'] ?? 'DESC';
    $filters = [];
    return $this->pdoFactory->paginate(
      'client_api_keys',
      (int) $page,
      (int) $perPage,
      $filters,
      $sortBy,
      $sortOrder
    );
  }

  public function getById(int $id): ?array
  {
    try {
      $apiKey = $this->pdoFactory->find('client_api_keys', $id);
      if (!$apiKey) {
        return null;
      }
      return $apiKey;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getByApiKey(string $apiKey): ?array
  {
    try {
      $sql = $this->pdo->prepare("SELECT * FROM client_api_keys WHERE api_key = :api_key");
      $sql->bindValue(':api_key', $apiKey);
      $sql->execute();
      $result = $sql->fetch(\PDO::FETCH_ASSOC);
      if (!$result) {
        return null;
      }
      return $result;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getByClientIdAndEnvironment(int $clientId, string $environment): ?array
  {
    try {
      $sql = $this->pdo->prepare("SELECT * FROM client_api_keys WHERE client_id = :client_id AND environment = :environment");
      $sql->bindValue(':client_id', $clientId);
      $sql->bindValue(':environment', $environment);
      $sql->execute();
      $result = $sql->fetch(\PDO::FETCH_ASSOC);
      if (!$result) {
        return null;
      }
      return $result;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getAllByClientId(int $clientId): array
  {
    try {
      $sql = $this->pdo->query("SELECT * FROM client_api_keys cak WHERE cak.client_id = $clientId");
      $response = $sql->fetchAll(\PDO::FETCH_ASSOC);
      if (!$response) {
        return [];
      }
      return $response;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getAllActiveByClientId(int $clientId): array
  {
    try {
      $sql = $this->pdo->query("SELECT * FROM client_api_keys cak WHERE cak.client_id = $clientId AND cak.status = 'ACTIVE'");
      $response = $sql->fetchAll(\PDO::FETCH_ASSOC);
      if (!$response) {
        return [];
      }
      return $response;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function deactivateKey(array $data): void
  {
    $this->pdoFactory->deactivateKey($data['client_id'], $data['environment']);
  }

  public function updateLastUsedAt(int $id, string $dateTime): void
  {
    $this->pdoFactory->updateLastUsedAt($id, $dateTime);
  }

  public function deactivateById(int $id): void
  {
    try {
      if (is_null($id)) {
        throw new \Exception('Client ID cannot be null');
      }
      $query = "UPDATE client_api_keys cak SET status = 'REVOKED' WHERE cak.id = :id AND cak.status = 'ACTIVE'";
      $stmt = $this->pdo->prepare($query);
      $stmt->bindValue(':id', $id);
      $stmt->execute();
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }
}
