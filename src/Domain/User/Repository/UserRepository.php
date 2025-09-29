<?php

namespace App\Domain\User\Repository;

use App\Domain\User\Data\UserItem;
use App\Domain\Role\Data\RoleItem;
use App\Domain\SignatureCredit\Data\SignatureCreditItem;
use App\Factory\PdoFactory;
use App\Database\PdoConnection;
use App\Traits\PaginateTrait;
use App\Factory\Pagination\PageRequest;

final class UserRepository
{
  use PaginateTrait;
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
      'users', 
      (int) $page, 
      (int) $perPage, 
      $filters, 
      $sortBy, 
      $sortOrder
    );
  }

  public function getRoleForUser(int $roleId): RoleItem
  {
    try {
      $role = null;
      $sql = $this->pdo->query("SELECT r.* FROM roles r WHERE r.id = $roleId");
      $response = $sql->fetch(\PDO::FETCH_ASSOC);
      $role = new RoleItem();
      $role->id = $response['id'];
      $role->name = $response['name'];
      $role->code = $response['code'];
      $role->description = $response['description'];
      $role->is_active = $response['is_active'];
      return $role;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getSignatureCreditsForUser(int $signatureCreditId): SignatureCreditItem
  {
    try {
      $signatureCredit = null;
      $sql = $this->pdo->query("SELECT * FROM signature_credits WHERE id = $signatureCreditId");
      $response = $sql->fetch(\PDO::FETCH_ASSOC);
      if ($response) {
        $signatureCredit = new SignatureCreditItem();
        $signatureCredit->id = $response['id'];
        $signatureCredit->consumed_quantity = (int)$response['consumed_quantity'];
        $signatureCredit->remaining_quantity = (int)$response['remaining_quantity'];
        $signatureCredit->created_at = $response['created_at'];
      }
      return $signatureCredit;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getBySignatureCreditId(int $creditId): mixed
  {
    try {
      $sql = $this->pdo->query("SELECT u.* FROM users u INNER JOIN signature_credits sc ON sc.id = u.signature_credit_id WHERE sc.id = $creditId");
      $user = $sql->fetch(\PDO::FETCH_ASSOC);
      return $user;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getById(int $id): array
  {
    $user = $this->pdoFactory->find('users', $id);
    if (!$user) {
      throw new \DomainException(sprintf('User not found: %s', $id));
    }

    return $user;
  }

  public function save(UserItem $user): string
  {
    return $this->pdoFactory->create('users', $this->toRow($user));
  }

  public function update(int $id, UserItem $user): void
  {
    $this->pdoFactory->update('users', $id, $this->toRowUpdate($user));
  }

  public function updateById(int $id, array $data): void
  {
    $this->pdoFactory->update('users', $id, $data);
  }

  public function delete(int $id): void
  {
    $this->pdoFactory->delete('users', $id);
  }

  public function toRow(UserItem $user): array
  {
    $row = [
      'uuid' => $user->uuid,
      'rfc' => $user->rfc,
      'first_name' => $user->first_name,
      'last_name' => $user->last_name,
      'mother_last_name' => $user->mother_last_name,
      'full_name' => trim("{$user->first_name} {$user->last_name} {$user->mother_last_name}"),
      'username' => $user->username,
      'email' => $user->email,
      'password' => $user->password,
      'phone' => $user->phone,
      'role_id' => (int)$user->role,
      'client_id' => ($user->client_id) ? (int)$user->client_id : null,
      'pass_reset' => (int)$user->pass_reset ?? (int) false,
    ];
    return $row;
  }

  public function toRowUpdate(UserItem $user): array
  {
    $row = [
      'rfc' => $user->rfc,
      'first_name' => $user->first_name,
      'last_name' => $user->last_name,
      'mother_last_name' => $user->mother_last_name,
      'full_name' => trim("{$user->first_name} {$user->last_name} {$user->mother_last_name}"),
      'username' => $user->username,
      'email' => $user->email,
      'phone' => $user->phone,
      'role_id' => (int)$user->role,
    ];
    return $row;
  }

  public function checkUserExists(string $email): bool
  {
    $exist = $this->pdoFactory->findAllBy('users', 'email', $email);
    return (bool)count($exist) > 0;
  }
  
  public function changePassword(int $id, array $data): void
  {
    $this->pdoFactory->updateBy('users', 'id', $id, $data);
  }

  public function getUserByTerm(array $pagination): array
  {
    $pageRequest = PageRequest::of(
      $pagination['page'] ?? 1,
      $pagination['per_page'] ?? 10,
      $pagination['sort_by'] ?? 'id',
      $pagination['sort_order'] ?? 'ASC',
    );
    $term = isset($pagination['term']) ? $pagination['term'] : null;
    $query = "SELECT * FROM users WHERE 
              (full_name LIKE '%{$term}%' OR email LIKE '%{$term}%' OR NULLIF('{$term}', '') IS NULL)
              AND role_id != 1";
    $data = $this->paginateByQuery($query, $pageRequest);
    return $data->toArray();
  }

  public function getByClientId(int $clientId): array
  {
    $user = $this->pdoFactory->findByColumn('users', 'client_id', $clientId);
    if (!$user) {
      throw new \DomainException(sprintf('Users not found for client id: %s', $clientId));
    }

    return $user;
  }
}
