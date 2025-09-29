<?php

namespace App\Domain\SignaturePackagePurchase\Repository;

use App\Domain\SignaturePackagePurchase\Data\SignaturePackagePurchaseItem;
use App\Factory\PdoFactory;
use App\Database\PdoConnection;

final class SignaturePackagePurchaseRepository
{
  private PdoFactory $pdoFactory;
  public PdoConnection $pdo;


  public function __construct(PdoFactory $pdoFactory, PdoConnection $pdo)
  {
    $this->pdoFactory = $pdoFactory;
    $this->pdo = $pdo;
  }

  public function save(SignaturePackagePurchaseItem $purchase): string
  {
    return $this->pdoFactory->create('signature_packages_purchases', $this->toRow($purchase));
  }

  public function toRow(SignaturePackagePurchaseItem $purchase): array
  {
    $row = [
      'credit_id' => $purchase->credit_id,
      'package_id' => $purchase->package_id,
      'quantity' => $purchase->quantity,
      'total_price' => $purchase->total_price,
      'total_iva' => $purchase->total_iva,
      'amount' => $purchase->amount,
    ];
    return $row;
  }

  public function getAll(): array
  {
    return $this->pdoFactory->all('signature_packages_purchases');
  }

  public function getById(int $id): array
  {
    $purchase = $this->pdoFactory->find('signature_packages_purchases', $id);
    if (!$purchase) {
      throw new \DomainException(sprintf('Item not found: %s', $id));
    }

    return $purchase;
  }

  public function updateById(int $id, array $data): void
  {
    $this->pdoFactory->update('signature_packages_purchases', $id, $data);
  }

  public function deleteById(int $id): void
  {
    $this->pdoFactory->delete('signature_packages_purchases', $id);
  }

  public function getAllByUserId(int $userId): array
  {
    try {
      $sql = $this->pdo->query("SELECT spp.* FROM signature_packages_purchases spp INNER JOIN signature_credits sc ON sc.id = spp.credit_id INNER JOIN users u ON u.signature_credit_id = sc.id WHERE u.id = $userId");
      $response = $sql->fetchAll(\PDO::FETCH_ASSOC);
      return $response;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getPaymentByPurchaseId(int $purchaseId): array
  {
    try {
      $sql = $this->pdo->query("SELECT * FROM signer_payment WHERE purchase_id = $purchaseId");
      $response = $sql->fetch(\PDO::FETCH_ASSOC);
      return $response;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }
}
