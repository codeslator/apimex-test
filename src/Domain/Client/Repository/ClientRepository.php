<?php

namespace App\Domain\Client\Repository;

use App\Domain\Client\Data\ClientItem;
use App\Factory\PdoFactory;
use App\Database\PdoConnection;

final class ClientRepository
{
  private PdoFactory $pdoFactory;
  public PdoConnection $pdo;

  public function __construct(PdoFactory $pdoFactory, PdoConnection $pdo)
  {
    $this->pdoFactory = $pdoFactory;
    $this->pdo = $pdo;
  }

  public function save(ClientItem $client): string
  {
    return $this->pdoFactory->create('clients', $this->toRow($client));
  }

  public function toRow(ClientItem $client): array
  {
    $row = [
      'uuid' => $client->uuid,
      'name' => $client->name,
      'rfc' => $client->rfc,
      'description' => $client->description,
      'contact_id' => $client->contact_id,
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
      'clients', 
      (int) $page, 
      (int) $perPage, 
      $filters, 
      $sortBy, 
      $sortOrder
    );
  }

  public function getById(int $id): array
  {
    $client = $this->pdoFactory->find('clients', $id);
    if (!$client) {
      throw new \DomainException(sprintf('Client not found: %s', $id));
    }

    return $client;
  }

  public function disable(int $id): void
  {
    try {
      $stmt = $this->pdo->prepare('UPDATE clients SET is_active = 0, updated_at = NOW() WHERE id = :id');
      $stmt->bindValue(':id', $id);
      $stmt->execute();
    } catch (\Exception $e) {
      throw new \RuntimeException('Failed to disable client: ' . $e->getMessage());
    }
  }
}
