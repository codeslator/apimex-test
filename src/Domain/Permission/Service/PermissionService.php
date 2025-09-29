<?php

namespace App\Domain\Permission\Service;

use App\Domain\Permission\Repository\PermissionRepository;
use App\Domain\Permission\Data\PermissionItem;

final class PermissionService
{

  private PermissionRepository $repository;

  public function __construct(PermissionRepository $repository)
  {
    $this->repository = $repository;
  }

  public function getAll(array $pagination): array
  {
    $data = $this->repository->getAll($pagination);
    if (count($data['data']) > 0) {
      $rows = [];
      foreach ($data['data'] as $permission) {
        $rows[] = $this->transform($permission);
      }
      $data['data'] = $rows;
    }
    return $data;
  }

  public function create(array $data): void
  {
    $permission = new PermissionItem();
    $permission->name = $data['name'];
    $permission->description = $data['description'];
    $permission->code = $data['code'];
    $this->repository->save($permission);
  }

  public function getById(int $id): PermissionItem
  {
    return $this->transform($this->repository->getById($id));
  }

  public function transform(array $row): PermissionItem
  {
    $permission = new PermissionItem();
    $permission->id = $row['id'];
    $permission->code = $row['code'];
    $permission->name = $row['name'];
    $permission->description = $row['description'];
    return $permission;
  }

  public function update(int $id, array $data): void
  {
    $this->repository->update($id, $data);
  }

  public function delete(int $id): void
  {
    $this->repository->delete($id);
  }

  public function hasPermission(string $roleCode, string $permissionCode): bool
  {
    $permissions = $this->repository->getPermissionsByRole($roleCode);
    return in_array($permissionCode, $permissions, true);
  }

  public function getPermissionsByRole(string $roleCode): array
  {
    return $this->repository->getPermissionsByRole($roleCode);
  }
}
