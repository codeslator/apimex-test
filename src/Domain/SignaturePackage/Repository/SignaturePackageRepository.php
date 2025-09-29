<?php

namespace App\Domain\SignaturePackage\Repository;

use App\Domain\SignaturePackage\Data\SignaturePackageItem;
use App\Factory\PdoFactory;

final class SignaturePackageRepository
{
  private PdoFactory $pdoFactory;

  public function __construct(PdoFactory $pdoFactory)
  {
    $this->pdoFactory = $pdoFactory;
  }

  public function save(SignaturePackageItem $package): void
  {
    $this->pdoFactory->create('signature_packages', $this->toRow($package));
  }

  public function toRow(SignaturePackageItem $package): array
  {
    $row = [
      'uuid' => $package->uuid,
      'name' => $package->name,
      'price_per_signature' => $package->price_per_signature,
      'iva' => $package->iva,
      'min_quantity' => $package->min_quantity,
      'max_quantity' => $package->max_quantity,
    ];
    return $row;
  }

  public function getAll(): array
  {
    return $this->pdoFactory->all('signature_packages');
  }

  public function getById(int $id): array
  {
    $package = $this->pdoFactory->find('signature_packages', $id);
    if (!$package) {
      throw new \DomainException(sprintf('Package not found: %s', $id));
    }

    return $package;
  }

  public function updateById(int $id, array $data): void
  {
    $this->pdoFactory->update('signature_packages', $id, $data);
  }

  public function deleteById(int $id): void
  {
    $this->pdoFactory->delete('signature_packages', $id);
  }
}
