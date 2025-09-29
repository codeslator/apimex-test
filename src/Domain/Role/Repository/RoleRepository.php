<?php

namespace App\Domain\Role\Repository;

use App\Factory\PdoFactory;
use App\Domain\Role\Data\RoleItem;
use App\Domain\Permission\Data\PermissionItem;
use App\Database\PdoConnection;


final class RoleRepository
{
  private PdoFactory $pdoFactory;
  public PdoConnection $pdo;
  public function __construct(PdoFactory $pdoFactory, PdoConnection $pdoConnection)
  {
    $this->pdoFactory = $pdoFactory;
    $this->pdo = $pdoConnection;
  }

  public function getAll(array $pagination): array
  {
    $page = $pagination['page'] ?? 1;
    $perPage = $pagination['per_page'] ?? 10;
    $sortBy = $pagination['sort_by'] ?? 'id';
    $sortOrder = $pagination['sort_order'] ?? 'ASC';
    $filters = (isset($pagination['filters']) && $pagination['filters'] !== '') ? json_decode($pagination['filters'], true) : [];
    return $this->pdoFactory->paginate(
      'roles',
      (int) $page,
      (int) $perPage,
      $filters,
      $sortBy,
      $sortOrder
    );
  }

  public function getPermissionsForRole(int $roleId): array
  {
    try {
      $permissions = [];
      $sql = $this->pdo->query("SELECT p.* FROM permissions p INNER JOIN roles_permission rp ON rp.permission_id = p.id WHERE rp.role_id = $roleId");
      $response = $sql->fetchAll(\PDO::FETCH_ASSOC);
      foreach ($response as $row) {
        $permission = new PermissionItem();
        $permission->id = $row['id'];
        $permission->name = $row['name'];
        $permission->description = $row['description'];
        $permission->code = $row['code'];
        $permissions[] = $permission;
      }
      return $permissions;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function save(RoleItem $role): void
  {
    $roleId = $this->pdoFactory->create('roles', $this->toRow($role));
    foreach ($role->permissions as $permission) {
      $this->pdoFactory->create('roles_permission', $this->toRowRelation($permission, $roleId));
    }
  }

  public function getById(int $id): array
  {
    $role = $this->pdoFactory->find('roles', $id);
    if (!$role) {
      throw new \DomainException(sprintf('Permission not found: %s', $id));
    }

    return $role;
  }

  public function toRow(RoleItem $role): array
  {
    $row = [
      'name' => $role->name,
      'code' => $role->code,
      'description' => $role->description,
      'is_active' => $role->is_active,
    ];
    return $row;
  }

  public function toRowRelation(string $permission, string $roleId): array
  {
    $row = [
      'role_id' => $roleId,
      'permission_id' => $permission,
    ];
    return $row;
  }

  public function update(int $id, array $role): void
  {
    $this->pdo->beginTransaction();
    if (isset($role['permissions']) && !empty($role['permissions'])) {
      $this->deletePermissionsByRoleId($id);
      foreach ($role['permissions'] as $permission) {
        $this->pdoFactory->create('roles_permission', $this->toRowRelation($permission, $id));
      }
      unset($role['permissions']);
    }
    if (!empty($role)) {
      $this->pdoFactory->update('roles', $id, $role);
    }
    $this->pdo->commit();
  }
  
  public function deletePermissionsByRoleId(int $roleId): void
  {
    $this->pdoFactory->deleteByCondition('roles_permission', ['role_id' => $roleId]);
  }

  public function delete(int $id): void
  {
    $this->deletePermissionsByRoleId($id);
    $this->pdoFactory->delete('roles', $id);
  }

  public function getByCode(string $code): array
  {
    $sql = $this->pdo->prepare("SELECT * FROM roles WHERE code = :code");
    $sql->bindValue(':code', $code);
    $sql->execute();
    $row = $sql->fetch(\PDO::FETCH_ASSOC);
    return $row;
  }
}
