<?php

namespace App\Domain\SignaturePackage\Service;

use App\Domain\SignaturePackage\Repository\SignaturePackageRepository;
use App\Domain\SignaturePackage\Data\SignaturePackageItem;
use Ramsey\Uuid\Uuid;

final class SignaturePackageService
{

  private SignaturePackageRepository $repository;

  public function __construct(SignaturePackageRepository $repository)
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
    $uuid = Uuid::uuid4();
    $package = new SignaturePackageItem();
    $package->name = $data['name'];
    $package->uuid = $uuid->toString();
    $package->price_per_signature = $data['price_per_signature'];
    $package->iva = $data['iva'];
    $package->min_quantity = $data['min_quantity'];
    $package->max_quantity = $data['max_quantity'];
    $this->repository->save($package);
  }

  public function getById(int $id): SignaturePackageItem
  {
    return $this->transform($this->repository->getById($id));
  }

  public function transform(array $row): SignaturePackageItem
  {
    $package = new SignaturePackageItem();
    $package->id = (int)$row['id'];
    $package->name = $row['name'];
    $package->uuid = $row['uuid'];
    $package->price_per_signature = (float)$row['price_per_signature'];
    $package->iva = (float)$row['iva'];
    $package->min_quantity = (int)$row['min_quantity'];
    $package->max_quantity = (int)$row['max_quantity'];
    $package->created_at = $row['created_at'];
    return $package;
  }

  public function updateById(int $id, array $data): void
  {
    $this->repository->updateById($id, $data);
  }

  public function deleteById(int $id): void
  {
    $this->repository->deleteById($id);
  }
}
