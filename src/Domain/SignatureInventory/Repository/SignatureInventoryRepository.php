<?php

namespace App\Domain\SignatureInventory\Repository;

use App\Domain\SignatureInventory\Data\SignatureInventoryItem;
use App\Factory\PdoFactory;
use App\Database\PdoConnection;

final class SignatureInventoryRepository
{
  private PdoFactory $pdoFactory;
  public PdoConnection $pdo;

  public function __construct(PdoFactory $pdoFactory, PdoConnection $pdo)
  {
    $this->pdoFactory = $pdoFactory;
    $this->pdo = $pdo;

  }

  public function save(SignatureInventoryItem $inventory): void
  {
    $this->pdoFactory->create('signature_inventory', $this->toRow($inventory));
  }

  public function toRow(SignatureInventoryItem $inventory): array
  {
    $row = [
      'quantity' => $inventory->quantity,
      'source' => $inventory->source
    ];
    return $row;
  }

  public function getAll(): array
  {
    return $this->pdoFactory->all('signature_inventory');
  }

  public function getById(int $id): array
  {
    $inventory = $this->pdoFactory->find('signature_inventory', $id);
    if (!$inventory) {
      throw new \DomainException(sprintf('Item not found: %s', $id));
    }

    return $inventory;
  }

  public function updateById(int $id, $data): void
  {
    $this->pdoFactory->updateBy('signature_inventory', 'id', $id, $data);
  }

  public function updateByConditions(array $conditions, array $data): void
  {
    $this->pdoFactory->updateByConditions('signature_inventory', $conditions, $data);
  }

  public function getBySource(string $source): array
  {
    try {
      $sql = $this->pdo->query("SELECT * FROM signature_inventory WHERE source = '$source'");
      $file = $sql->fetch(\PDO::FETCH_ASSOC);
      return $file;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }
}
