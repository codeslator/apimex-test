<?php

namespace App\Domain\Payment\Repository;

use App\Domain\Payment\Data\PaymentItem;
use App\Factory\PdoFactory;
use App\Database\PdoConnection;

final class PaymentRepository
{
  private PdoFactory $pdoFactory;
  public PdoConnection $pdo;


  public function __construct(PdoFactory $pdoFactory, PdoConnection $pdo)
  {
    $this->pdoFactory = $pdoFactory;
    $this->pdo = $pdo;
  }

  public function save(PaymentItem $payment): void
  {
    $this->pdoFactory->create('signer_payment', $this->toRow($payment));
  }

  public function toRow(PaymentItem $payment): array
  {
    $row = [
      'signer_id' => $payment->signer_id,
      'purchase_id' => $payment->purchase_id,
      'payer_id' => $payment->payer_id,
      'invoice_id' => $payment->invoice_id,
      'amount' => $payment->amount,
      'method_type' => $payment->method_type,
      'payment_link' => $payment->payment_link,
      'info_link_creator' => $payment->info_link_creator,
    ];
    return $row;
  }

  public function getAll(array $pagination): array
  {
    $page = $pagination['page'] ?? 1;
    $perPage = $pagination['per_page'] ?? 10;
    $sortBy = $pagination['sort_by'] ?? 'id';
    $sortOrder = $pagination['sort_order'] ?? 'DESC';
    $filters = (isset($pagination['filters']) && $pagination['filters'] !== '') ? json_decode($pagination['filters'], true) : [];
    return $this->pdoFactory->paginate(
      'signer_payment',
      (int) $page, 
      (int) $perPage, 
      $filters, 
      $sortBy, 
      $sortOrder
    );
  }

  public function getById(int $id): array
  {
    $payment = $this->pdoFactory->find('signer_payment', $id);
    if (!$payment) {
      throw new \DomainException(sprintf('Payment not found: %s', $id));
    }

    return $payment;
  }

  public function updateByInvoiceId(string $invoiceId, array $data): void
  {
    $payment = $this->pdoFactory->findExactBy('signer_payment', 'invoice_id', $invoiceId);

    if (!$payment) {
      throw new \DomainException(sprintf('Signer not found'));
    }
    $this->pdoFactory->updateBy('signer_payment', 'invoice_id', $invoiceId, $data);
  }

  public function getByInvoiceIdWithDocument(string $invoiceId): array
  {
    try {
      $sql = $this->pdo->query("SELECT sp.*, s.document_id FROM signer_payment sp INNER JOIN signers s ON s.id = sp.signer_id WHERE invoice_id = '$invoiceId'");
      $payment = $sql->fetch(\PDO::FETCH_ASSOC);
      return $payment;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getByInvoiceId(string $invoiceId): array
  {
    try {
      $sql = $this->pdo->query("SELECT * FROM signer_payment WHERE invoice_id = '$invoiceId'");
      $payment = $sql->fetch(\PDO::FETCH_ASSOC);
      return $payment;
    } catch (\PDOException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function deleteBySignerId(int $signerId): void
  {
    $this->pdoFactory->deleteBy('signer_payment', 'signer_id', $signerId);
  }
}