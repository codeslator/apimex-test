<?php

namespace App\Domain\SignatureInventory\Service;

use App\Domain\SignatureInventory\Repository\SignatureInventoryRepository;
use App\Domain\SignatureInventory\Data\SignatureInventoryItem;
use App\Domain\SignatureInventory\Data\SignatureInventorySource;
use App\Domain\SignatureInventory\Data\SignatureInventoryMode;

final class SignatureInventoryService
{

  private SignatureInventoryRepository $repository;

  public function __construct(SignatureInventoryRepository $repository)
  {
    $this->repository = $repository;
  }

  public function getAll(): array
  {
    $rows = [];
    foreach ($this->repository->getAll() as $row) {
      $rows[] = $this->transform($row);
    }
    return $rows;
  }

  public function create(array $data): void
  {
    $inventory = new SignatureInventoryItem();
    $inventory->quantity = $data['quantity'];
    $inventory->source = SignatureInventorySource::from($data['source'])->value;
    $this->repository->save($inventory);
  }

  public function getById(int $id): SignatureInventoryItem
  {
    return $this->transform($this->repository->getById($id));
  }

  public function updateById(int $id, array $data): void
  {
    $inventory = $this->repository->getById($id);

    if ($data['mode'] == SignatureInventoryMode::INCREMENT->value) {
      $quantity = (int)$inventory['quantity'] + (int)$data['quantity'];
    }
    else if ($data['mode'] == SignatureInventoryMode::DECREMENT->value) {
      if ((int)$data['quantity'] > (int)$inventory['quantity']) {
        throw new \DomainException("The decrement quantity value can't be major than inventory quantity.");
      }
      $quantity = (int)$inventory['quantity'] - (int)$data['quantity'];
    }
    else {
      $quantity = (int)$data['quantity'];
    }

    $inventoryData = [
      'quantity' => $quantity
    ];
    $this->repository->updateById($id, $inventoryData);
  }

  public function transform(array $row): SignatureInventoryItem
  {
    $inventory = new SignatureInventoryItem();
    $inventory->id = (int)$row['id'];
    $inventory->quantity = (int)$row['quantity'];
    $inventory->source = $row['source'];
    $inventory->created_at = $row['created_at'];
    return $inventory;
  }
}
