<?php

namespace App\Domain\SignatureCredit\Repository;

use App\Domain\SignatureCredit\Data\SignatureCreditItem;
use App\Factory\PdoFactory;
use App\Database\PdoConnection;

final class SignatureCreditRepository
{
  private PdoFactory $pdoFactory;
  public PdoConnection $pdo;

  public function __construct(PdoFactory $pdoFactory, PdoConnection $pdo)
  {
    $this->pdoFactory = $pdoFactory;
    $this->pdo = $pdo;
  }

  public function save(SignatureCreditItem $credit): string
  {
    return $this->pdoFactory->create('signature_credits', $this->toRow($credit));
  }

  public function toRow(SignatureCreditItem $credit): array
  {
    $row = [
      'remaining_quantity' => $credit->remaining_quantity,
      // 'consumed_quantity' => $credit->consumed_quantity,
      // 'from_date' => $credit->from_date,
      // 'to_date' => $credit->to_date,
    ];
    return $row;
  }

  public function getAll(): array
  {
    return $this->pdoFactory->all('signature_credits');
  }

  public function getById(int $id): ?array
  {
    $signatureCredit = $this->pdoFactory->find('signature_credits', $id);
    if (!$signatureCredit) {
      return null;
    }
    return $signatureCredit;
  }

  public function updateById(int $id, array $data): void
  {
    $this->pdoFactory->update('signature_credits', $id, $data);
  }

  public function getByUserId(int $userId): ?array
  {
    $sql = $this->pdo->query("SELECT sc.* FROM signature_credits sc INNER JOIN users u ON u.signature_credit_id = sc.id WHERE u.id = $userId");
    $credit = $sql->fetch(\PDO::FETCH_ASSOC);
    if (!$credit) {
      return null;
    }
    return $credit;
  }
}
