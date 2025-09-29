<?php

namespace App\Domain\ClientContact\Repository;

use App\Domain\ClientContact\Data\ClientContactItem;
use App\Factory\PdoFactory;
use App\Database\PdoConnection;

final class ClientContactRepository
{
  private PdoFactory $pdoFactory;
  public PdoConnection $pdo;

  public function __construct(PdoFactory $pdoFactory, PdoConnection $pdo)
  {
    $this->pdoFactory = $pdoFactory;
    $this->pdo = $pdo;
  }

  public function save(ClientContactItem $contact): string
  {
    return $this->pdoFactory->create('client_contacts', $this->toRow($contact));
  }

  public function toRow(ClientContactItem $contact): array
  {
    $row = [
      'uuid' => $contact->uuid,
      'rfc' => $contact->rfc,
      'curp' => ($contact->curp) ? $contact->curp : null,
      'first_name' => $contact->first_name,
      'last_name' => $contact->last_name,
      'mother_last_name' => $contact->mother_last_name,
      'full_name' => trim("{$contact->first_name} {$contact->last_name} {$contact->mother_last_name}"),
      'email' => $contact->email,
      'phone' => $contact->phone,
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
      'client_contacts', 
      (int) $page, 
      (int) $perPage, 
      $filters, 
      $sortBy, 
      $sortOrder
    );
  }

  public function getById(int $id): array
  {
    $contact = $this->pdoFactory->find('client_contacts', $id);
    if (!$contact) {
      throw new \DomainException(sprintf('Contact not found: %s', $id));
    }

    return $contact;
  }

  public function getByClientId(int $clientId): array
  {
    $sql = "SELECT cc.* FROM client_contacts cc
            INNER JOIN clients c ON cc.id = c.contact_id
            WHERE c.id = :client_id";
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':client_id', $clientId, \PDO::PARAM_INT);
    $stmt->execute();
    $contact = $stmt->fetch();
    if (!$contact) {
      throw new \DomainException(sprintf('Contact not found for client ID: %s', $clientId));
    }
    return $contact;
  }
}
