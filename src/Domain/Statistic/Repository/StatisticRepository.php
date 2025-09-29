<?php

namespace App\Domain\Statistic\Repository;

use App\Factory\PdoFactory;
use App\Database\PdoConnection;

final class StatisticRepository
{
  private PdoFactory $pdoFactory;
  private PdoConnection $pdo;

  public function __construct(PdoFactory $pdoFactory, PdoConnection $pdo)
  {
    $this->pdoFactory = $pdoFactory;
    $this->pdo = $pdo;
  }

  public function getAllTotals(): array
  {
    try {
      $query = "SELECT 
                  (SELECT COUNT(*) FROM documents WHERE status = 'SIGNED') AS total_documents,
                  (SELECT COUNT(*) FROM users WHERE role_id != 1) AS total_users,
                  (SELECT COUNT(*) FROM signers s INNER JOIN documents d ON s.document_id = d.id WHERE s.is_signed = true AND d.status = 'SIGNED') AS total_signatures,
                  (SELECT COUNT(*) FROM signer_payment WHERE status = 'succeeded') AS total_payments,
                  (SELECT SUM(amount) FROM signer_payment WHERE status = 'succeeded') AS total_revenue,
                  (SELECT COUNT(*) FROM clients WHERE is_active = true) AS total_active_clients,
                  (SELECT COUNT(*) FROM client_api_keys WHERE status = 'ACTIVE') AS total_active_client_api_keys,
                  (SELECT SUM(request_count) FROM client_api_key_usage) AS total_api_requests;";
      $sql = $this->pdo->query($query);
      $response = $sql->fetch(\PDO::FETCH_ASSOC);
      return $response;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }
}