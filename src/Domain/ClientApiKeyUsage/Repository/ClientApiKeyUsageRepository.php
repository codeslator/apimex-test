<?php

namespace App\Domain\ClientApiKeyUsage\Repository;

use App\Factory\PdoFactory;
use App\Database\PdoConnection;
use App\Domain\ClientApiKeyUsage\Data\ClientApiKeyUsageData;

final class ClientApiKeyUsageRepository
{
  private PdoFactory $pdoFactory;
  public PdoConnection $pdo;

  public function __construct(PdoFactory $pdoFactory, PdoConnection $pdo)
  {
    $this->pdoFactory = $pdoFactory;
    $this->pdo = $pdo;
  }

  public function save(ClientApiKeyUsageData $usage): string
  {
    return $this->pdoFactory->create('client_api_key_usage', $this->toRow($usage));
  }

  public function toRow(ClientApiKeyUsageData $usage): array
  {
    $row = [
      'client_api_key_id' => $usage->client_api_key_id,
      'request_count' => $usage->request_count,
      'first_request_at' => $usage->first_request_at,
      'last_request_at' => $usage->last_request_at,
      'window_start' => $usage->window_start,
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
      'client_api_key_usage',
      (int) $page,
      (int) $perPage,
      $filters,
      $sortBy,
      $sortOrder
    );
  }

  public function getById(int $id): array
  {
    $usage = $this->pdoFactory->find('client_api_key_usage', $id);
    if (!$usage) {
      throw new \DomainException(sprintf('Usage record not found: %s', $id));
    }

    return $usage;
  }

  public function findByApiKeyAndWindow(array $data): array | bool
  {
    return $this->pdoFactory->findByConditions('client_api_key_usage', $data);
  }

  public function increment(int $usageId): void
  {
    $query = "UPDATE client_api_key_usage SET request_count = request_count + 1, last_request_at = NOW() WHERE id = :id";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindValue(':id', $usageId);
    $stmt->execute();
  }

  public function updateUsage(int $usageId, array $data): void
  {
    $fields = [];
    $params = [':id' => $usageId];

    if (isset($data['request_count'])) {
        $fields[] = 'request_count = :request_count';
        $params[':request_count'] = $data['request_count'];
    }
    if (isset($data['last_request_at'])) {
        $fields[] = 'last_request_at = :last_request_at';
        $params[':last_request_at'] = $data['last_request_at'];
    }

    if (empty($fields)) {
        return;
    }

    $query = "UPDATE client_api_key_usage SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $this->pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
  }

  public function getUsageByApiKeyId(int $apiKeyId): ?array
  {
    try {
      $query = "SELECT
                SUM(request_count) as total_requests,
                MIN(first_request_at) as first_request_at,
                MAX(last_request_at) as last_request_at 
                FROM client_api_key_usage WHERE client_api_key_id = :apiKeyId GROUP BY client_api_key_id LIMIT 1";
      $stmt = $this->pdo->prepare($query);
      $stmt->bindValue(':apiKeyId', $apiKeyId);
      $stmt->execute();
      $result = $stmt->fetch(\PDO::FETCH_ASSOC);
      if (!$result) {
        return null;
      }
      return $result;
    } catch (\Exception $e) {
      throw new \DomainException('Error fetching usage by API key ID: ' . $e->getMessage());
    }
  }
}
