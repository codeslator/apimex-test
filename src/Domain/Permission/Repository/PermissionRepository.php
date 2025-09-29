<?php

namespace App\Domain\Permission\Repository;

use App\Domain\Permission\Data\PermissionItem;
use App\Factory\PdoFactory;
use App\Database\PdoConnection;

final class PermissionRepository
{
  private PdoFactory $pdoFactory;
  public PdoConnection $pdo;


  public function __construct(PdoFactory $pdoFactory, PdoConnection $pdo)
  {
    $this->pdoFactory = $pdoFactory;
    $this->pdo = $pdo;
  }

  public function save(PermissionItem $permission): void
  {
    $this->pdoFactory->create('permissions', $this->toRow($permission));
  }

  public function toRow(PermissionItem $permission): array
  {
    $row = [
      'name' => $permission->name,
      'description' => $permission->description,
      'code' => $permission->code
    ];
    return $row;
  }

  public function getAll(array $pagination): array
  {
    $page = $pagination['page'] ?? 1;
    $perPage = $pagination['per_page'] ?? 10;
    $sortBy = $pagination['sort_by'] ?? 'id';
    $sortOrder = $pagination['sort_order'] ?? 'ASC';
    $filters = (isset($pagination['filters']) && $pagination['filters'] !== '') ? json_decode($pagination['filters'], true) : [];
    return $this->pdoFactory->paginate(
      'permissions',
      (int) $page, 
      (int) $perPage, 
      $filters, 
      $sortBy, 
      $sortOrder
    );
  }

  public function getById(int $id): array
  {
    $permission = $this->pdoFactory->find('permissions', $id);
    if (!$permission) {
      throw new \DomainException(sprintf('Permission not found: %s', $id));
    }

    return $permission;
  }

  public function delete(int $id): void
  {
    $this->pdoFactory->delete('permissions', $id);
  }

  public function update(int $id, array $data): void
  {
    $this->pdoFactory->update('permissions', $id, $data);
  }

  public function getPermissionsByRole(string $code): array
  {
    try {
      $query = "SELECT p.* FROM permissions p
                JOIN roles_permission rp ON p.id = rp.permission_id
                JOIN roles r ON rp.role_id = r.id
                WHERE r.code = :code";
      $stmt = $this->pdo->prepare($query);
      $stmt->bindValue(':code', $code);
      $stmt->execute();
      return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'code');
    } catch (\Exception $e) {
      throw new \DomainException('Error fetching permissions by code: ' . $e->getMessage());
    }
  }
}
