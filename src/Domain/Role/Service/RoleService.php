<?php

namespace App\Domain\Role\Service;

use App\Domain\Role\Repository\RoleRepository;
use App\Domain\Role\Data\RoleItem;

final class RoleService
{

  private RoleRepository $repository;

  public function __construct(RoleRepository $repository)
  {
    $this->repository = $repository;
  }

  public function getAll(array $pagination): array
  {
    $data = $this->repository->getAll($pagination);
    if (count($data['data']) > 0) {
      $rows = [];
      foreach ($data['data'] as $roles) {
        $rows[] = $this->transform($roles);
      }
      $data['data'] = $rows;
    }
    return $data;
  }

  public function create(array $data): void
  {
    $role = new RoleItem();
    $role->name = $data['name'];
    $role->code = $data['code'];
    $role->description = $data['description'];
    $role->is_active = $data['is_active'];
    $role->permissions = $data['permissions'];
    $this->repository->save($role);
  }

  public function getById(int $id): RoleItem
  {
    return $this->transform($this->repository->getById($id));
  }

  public function transform(array $row): RoleItem
  {
    $role = new RoleItem();
    $role->id = $row['id'];
    $role->name = $row['name'];
    $role->code = $row['code'];
    $role->description = $row['description'];
    $role->is_active = $row['is_active'];
    $role->permissions = $this->repository->getPermissionsForRole($row['id']);
    return $role;
  }

  public function update(int $id, array $data): void
  {
    $role = $this->repository->getById($id);
    if (!$role) {
      throw new \DomainException(sprintf('Role not found: %s', $id));
    }
    $this->repository->update((int) $id, $data);
  }
}
