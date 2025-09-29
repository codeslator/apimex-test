<?php

namespace App\Domain\SignaturePackagePurchase\Service;

use App\Domain\SignaturePackagePurchase\Repository\SignaturePackagePurchaseRepository;
use App\Domain\User\Repository\UserRepository;
use App\Domain\SignatureCredit\Repository\SignatureCreditRepository;
use App\Domain\SignaturePackage\Repository\SignaturePackageRepository;
use App\Domain\Payment\Repository\GigStackRepository;
use App\Domain\Payment\Service\PaymentService;
use App\Domain\SignaturePackagePurchase\Utilities\SignaturePackagePurchaseValidator;
use App\Domain\SignaturePackagePurchase\Data\SignaturePackagePurchaseItem;
use App\Domain\SignatureCredit\Data\SignatureCreditItem;
use App\Domain\Payment\Data\PaymentItem;

final class SignaturePackagePurchaseService
{

  private SignaturePackagePurchaseRepository $repository;
  private UserRepository $userRepository;
  private SignatureCreditRepository $creditRepository;
  private SignaturePackageRepository $packageRepository;
  private GigStackRepository $gigStackRepository;
  private PaymentService $paymentService;
  private SignaturePackagePurchaseValidator $validator;

  public function __construct(
    SignaturePackagePurchaseRepository $repository,
    UserRepository $userRepository,
    SignatureCreditRepository $creditRepository,
    SignaturePackageRepository $packageRepository,
    GigStackRepository $gigStackRepository,
    PaymentService $paymentService,
    SignaturePackagePurchaseValidator $validator
  ) {
    $this->repository = $repository;
    $this->userRepository = $userRepository;
    $this->creditRepository = $creditRepository;
    $this->packageRepository = $packageRepository;
    $this->gigStackRepository = $gigStackRepository;
    $this->paymentService = $paymentService;
    $this->validator = $validator;
  }

  public function getAll(): array
  {
    $rows = [];
    foreach ($this->repository->getAll() as $row) {
      $rows[] = $this->transform($row);
    }
    return $rows;
  }

  public function create(array $data): void
  {
    $purchase = new SignaturePackagePurchaseItem();
    $purchase->credit_id = $data['credit_id'];
    $purchase->package_id = $data['package_id'];
    $purchase->quantity = $data['quantity'];
    $purchase->total_price = $data['total_price'];
    $purchase->total_iva = $data['total_iva'];
    $purchase->amount = $data['amount'];
    $this->repository->save($purchase);
  }

  public function createFromPurchase(array $data, object $loggedUser): void
  {
    $this->validator->validatePurchase($data);
    // Validar los campos de entrada

    if ($loggedUser->id !== (int)$data['user_id'] && $loggedUser->role->name !== 'ADMIN') {
      throw new \DomainException('Access denied.');
    }
    
    if ($data['quantity'] <= 0) {
      throw new \DomainException('Quantity must be greater than zero.');
    }

    $package = $this->packageRepository->getById((int)$data['package_id']); 
    $user = $this->userRepository->getById((int)$data['user_id']); 
    
    $creditId = $user['signature_credit_id'];
    
    // Validar si el usuario ha comprado creditos anteriormente
    if(empty($creditId)) { // Si no ha tenido creditos, es primera vez que compra, crea un registro del credito
      $credit = new SignatureCreditItem();
      $credit->remaining_quantity = 0;
      $creditId = $this->creditRepository->save($credit);

      $userData = [
        'signature_credit_id' => $creditId
      ];

      $this->userRepository->updateById($user['id'], $userData); // Agrega el id del credito al usuario.
    }
    
    // Crear orden de compra
    $totalPrice = $package['price_per_signature'] * $data['quantity'];
    $totalIva = $totalPrice * $package['iva'];
    $purchase = new SignaturePackagePurchaseItem();
    $purchase->credit_id = $creditId;
    $purchase->package_id = $package['id'];
    $purchase->quantity = $data['quantity'];
    $purchase->total_price = $totalPrice;
    $purchase->total_iva = $totalIva;
    $purchase->amount = $totalPrice + $totalIva;
    $id = $this->repository->save($purchase);

    // Generar factura de pago para enviar al usuario
    $invoiceData = [
      'full_name' => $user['first_name'] . ' ' . $user['last_name'] . ' ' . $user['mother_last_name'],
      'email' => $user['email'],
      'item_name' => 'Paquete de Firmas: ' . $package['name'] . ' (' . $data['quantity'] . ')',
      'item_price' => $totalPrice,
      'item_tax' => $package['iva'],
      'package_id' => $data['package_id'],
    ];
    $invoice = $this->gigStackRepository->generateInvoice($invoiceData);
    $response = $this->gigStackRepository->generatePaymentLink($invoice);

    $iva = $totalPrice * (float) $package['iva'];

    $paymentData = [
      'purchase_id' => $id,
      'payment_link' => $response->data->shortURL,
      'invoice_id' => $response->data->fid,
      'method_type' => $response->data->custom_method_types[0]->id,
      'info_link_creator' => json_encode($response),
      'amount' => $totalPrice + $iva,
      'payer_id' => $user['id'],
    ];
    // Create payment after generate invoice and signature
    $this->paymentService->create($paymentData);
    // Se envia el mail de pago del paquete al usuario
  }

  public function getById(int $id): SignaturePackagePurchaseItem
  {
    return $this->transform($this->repository->getById($id));
  }

  public function transform(array $row): SignaturePackagePurchaseItem
  {
    $purchase = new SignaturePackagePurchaseItem();
    $purchase->id = (int)$row['id'];
    $purchase->credit_id = (int)$row['credit_id'];
    $purchase->package_id = (int)$row['package_id'];
    $purchase->quantity = (int)$row['quantity'];
    $purchase->total_price = (float)$row['total_price'];
    $purchase->total_iva = (float)$row['total_iva'];
    $purchase->amount = (float)$row['amount'];
    $purchase->is_paid = (bool)$row['is_paid'];
    $purchase->completed_at = $row['completed_at'];
    $purchase->payment_data = $this->transformPayment($this->repository->getPaymentByPurchaseId((int)$row['id']));
    $purchase->created_at = $row['created_at'];
    return $purchase;
  }

  public function updateById(int $id, array $data): void
  {
    $this->repository->updateById($id, $data);
  }

  public function deleteById(int $id): void
  {
    $this->repository->deleteById($id);
  }

  public function getAllByUserId(int $userId): array
  {
    $rows = [];
    foreach ($this->repository->getAllByUserId($userId) as $row) {
      $rows[] = $this->transform($row);
    }
    return $rows;
  }

  public function transformPayment(array $row): PaymentItem
  {
    $payment = new PaymentItem();
    $payment->id = $row['id'];
    $payment->purchase_id = $row['purchase_id'];
    $payment->invoice_id = $row['invoice_id'];
    $payment->payment_link = $row['payment_link'];
    $payment->status = $row['status'];
    $payment->method_type = $row['method_type'];
    $payment->created_at = $row['created_at'];
    return $payment;
  }
}
