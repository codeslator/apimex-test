<?php

namespace App\Domain\Payment\Service;

use App\Domain\Payment\Repository\PaymentRepository;
use App\Domain\Signature\Repository\SignatureRepository;
use App\Domain\Document\Repository\DocumentRepository;
use App\Domain\Biometry\Repository\BiometryRepository;
use App\Domain\User\Repository\UserRepository;
use App\Domain\SignaturePackagePurchase\Repository\SignaturePackagePurchaseRepository;
use App\Domain\SignatureCredit\Repository\SignatureCreditRepository;
use App\Domain\SignatureInventory\Repository\SignatureInventoryRepository;
use App\Domain\Payment\Data\PaymentItem;
use App\Domain\User\Data\UserItem;
use App\Domain\Signature\Data\SignatureRole;
use App\Domain\Mail\Service\MailService;
use chillerlan\QRCode\QRCode;
use App\Domain\Biometry\Data\BiometryCurrentStep;
use App\Domain\Document\Data\DocumentStatus;
use App\Domain\Document\Data\DocumentPaymentStatus;
use App\Domain\SignatureInventory\Data\SignatureInventorySource;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

final class PaymentService
{
  private PaymentRepository $repository;
  private SignatureRepository $signatureRepository;
  private DocumentRepository $documentRepository;
  private BiometryRepository $biometryRepository;
  private SignaturePackagePurchaseRepository $packagePurchaseRepository;
  private SignatureCreditRepository $creditRepository;
  private SignatureInventoryRepository $inventoryRepository;
  private UserRepository $userRepository;
  private MailService $mailService;
  private LoggerInterface $logger;
  private string $documentPath = __DIR__ . '/../../../../public/';

  public function __construct(
    PaymentRepository $repository,
    SignatureRepository $signatureRepository,
    DocumentRepository $documentRepository,
    BiometryRepository $biometryRepository,
    SignaturePackagePurchaseRepository $packagePurchaseRepository,
    SignatureCreditRepository $creditRepository,
    SignatureInventoryRepository $inventoryRepository,
    UserRepository $userRepository,
    MailService $mailService,
    LoggerFactory $loggerFactory
  ) {
    $this->repository = $repository;
    $this->signatureRepository = $signatureRepository;
    $this->documentRepository = $documentRepository;
    $this->biometryRepository = $biometryRepository;
    $this->packagePurchaseRepository = $packagePurchaseRepository;
    $this->creditRepository = $creditRepository;
    $this->inventoryRepository = $inventoryRepository;
    $this->userRepository = $userRepository;
    $this->mailService = $mailService;
    $this->logger = $loggerFactory
      ->addFileHandler('payments_logs.log')
      ->createLogger();
  }

  public function getAll(array $pagination): array
  {
    $data = $this->repository->getAll($pagination);
    if (count($data['data']) > 0) {
      $rows = [];
      foreach ($data['data'] as $payment) {
        $rows[] = $this->transform($payment);
      }
      $data['data'] = $rows;
    }
    return $data;
  }

  public function create(array $data): void
  {
    $payment = new PaymentItem();
    $payment->signer_id = $data['signer_id'] ?? null;
    $payment->purchase_id = $data['purchase_id'] ?? null;
    $payment->payer_id = $data['payer_id'] ?? null;
    $payment->payment_link = $data['payment_link'];
    $payment->invoice_id = $data['invoice_id'];
    $payment->amount = $data['amount'];
    $payment->method_type = $data['method_type'];
    $payment->info_link_creator = $data['info_link_creator'];
    $this->repository->save($payment);
  }

  public function webhook(array $data): mixed
  {
    $this->logger->info(sprintf('Payment data: %s', json_encode($data)));
    $this->logger->info(sprintf('Livemode: %s', $data['data']['livemode']));
    $livemode = (bool) $data['data']['livemode'];

    $url = $livemode ? $_SERVER['MAINURL'] : $_SERVER['DEMOURL'];

    $client = new Client();
    $response = $client->request('POST', "$url/api/payments/updatePayment", [
      'json' => $data
    ]);
    $this->logger->info(sprintf('Request completed in: %s', $url));
    return $response;
  }

  public function updatePayment(array $data): void
  {
    $this->logger->info(sprintf('Payment recieved data from GigStack: %s', json_encode($data)));

    $invoiceId = $data['data']['id'];
    $this->update($data); // Actualiza el registro del pago con datos de la pasarela;

    $payment = $this->repository->getByInvoiceId($invoiceId); // Obtiene la factura a travas del id de la factura con el id del documento

    if (!empty($payment['signer_id'])) { // Si el pago corresponde a las  firmas de un documento
      $this->updateSignaturePayment($payment);
    } else if (!empty($payment['purchase_id'])) { // Si el pago corresponde a un paquete de firmas
      $this->updatePackagePurchasePayment($payment);
    }
  }

  public function update(array $data): void
  {
    $paymentData = [
      'status' => $data['data']['status'],
      'response_payment_link' => json_encode($data),
      'completed_at' => date("Y-m-d H:i:s")
    ];

    $this->repository->updateByInvoiceId($data['data']['id'], $paymentData); // Actauliza el pago cuando se obtiene la factura
  }

  public function updateSignaturePayment(array $payment): void
  {
    $signer = $this->signatureRepository->getById((int)$payment['signer_id']);

    if ((bool)$signer['is_paid']) {
      throw new \DomainException(sprintf('This signature had been completed: %s', $payment['data']['id']));
    }

    if ($payment['status'] == 'succeeded') { // Verifica si el pago fue exitoso
      $roles = [
        SignatureRole::SIGNER->value,
        SignatureRole::SIGNER_PAYER->value,
      ];
      $signatures = $this->signatureRepository->getAllByDocumentIdAndRole((int)$signer['document_id'], $roles); // Obtiene todas las firmas con rol firmante o firmante/pagador y el document id
      $document = $this->documentRepository->getById((int)$signer['document_id']);


      foreach ($signatures as $signature) {
        $signatureConditions = [
          'document_id' => $document['id'],
          'id' => $signature['id'],
        ];

        $signatureData = [
          'is_paid' => true
        ];

        $this->signatureRepository->updateByConditions($signatureConditions, $signatureData); // Cacmbia el estado de las firmas a pagado
        // Actualiza los registros de la validacion bniometrica
        $this->sendBiometricValidation($signature, $document); // Envia el mail de validacion biometrica a todos los firmantes 
      }

      $documentData = [
        'status' => DocumentStatus::SIGNED_PENDING->value,
        'payment_status' => DocumentPaymentStatus::PAIDOUT->value
      ];
      $this->documentRepository->update((int)$document['id'], $documentData); // Finalmente actualiza el estado del documento
    }
  }

  public function updatePackagePurchasePayment(array $payment): void
  {
    $purchase = $this->packagePurchaseRepository->getById((int)$payment['purchase_id']); // Obtiene el registro de la compra
    $credit = $this->creditRepository->getById((int)$purchase['credit_id']); // Obtiene el registro del credito del usuario
    $user = $this->userRepository->getBySignatureCreditId((int)$credit['id']); // Obtiene el registro del usuario al que se le asigna el credito

    if ((bool)$purchase['is_paid']) {
      throw new \DomainException(sprintf('This purchase had been completed: %s', $payment['data']['id']));
    }

    if ($payment['status'] == 'succeeded') { // Verifica si el pago fue exitoso
      $purchaseData = [
        'is_paid' => true,
        'completed_at' => date('Y-m-d H:i:s')
      ];

      $this->packagePurchaseRepository->updateById((int)$purchase['id'], $purchaseData); // Actualiza el estado de la compra y asigna la fecha del pago

      $totalCredits = $credit['remaining_quantity'] + $purchase['quantity']; // Suma los creditos restantes y le suma lo que compro
      $creditData = [
        'remaining_quantity' => $totalCredits
      ];

      $this->creditRepository->updateById((int)$credit['id'], $creditData); // Actualiza el registro del credito

      $inventory = $this->inventoryRepository->getBySource(SignatureInventorySource::PSC_WORLD->value);

      $inventoryRemainingQuantity = $inventory['quantity'] - $purchase['quantity'];
      $inventoryData = [
        'quantity' => $inventoryRemainingQuantity
      ];
      $this->inventoryRepository->updateById((int)$inventory['id'], $inventoryData); // Actualiza el stock global de firmas

      $mailMetadata = [
        'domainUrl' => $_SERVER['DOMAINURL'],
        'supportEmail' => $_SERVER['SUPPORT_EMAIL'],
        'fullName' => $user['first_name'] . ' ' . $user['last_name'] . ' ' . $user['mother_last_name'],
        'totalCredits' => $purchase['quantity'],
      ];

      $subject = 'Se han asignado los créditos de firma a su cuenta de FirmaVirtual';
      $this->mailService->send($user['email'], $subject, 'signature_credits_added', $mailMetadata);
    }
  }

  public function getById(int $id): PaymentItem
  {
    return $this->transform($this->repository->getById($id));
  }

  public function deleteBySignerId(int $signerId): void
  {
    $this->repository->deleteBySignerId($signerId);
  }

  public function transform(array $row): PaymentItem
  {
    $payment = new PaymentItem();
    $payment->id = (int) $row['id'];
    $payment->signer_id = $row['signer_id'] ?? null;
    $payment->purchase_id = $row['purchase_id'] ?? null;
    $payment->payer = isset($row['payer_id']) ? $this->transformUser($this->userRepository->getById($row['payer_id'])) : null;
    $payment->status = ($row['status'] == 'succeeded') ? true : false;
    $payment->payment_link = $row['payment_link'];
    $payment->invoice_id = $row['invoice_id'];
    $payment->amount = $row['amount'] ?? 0;
    $payment->method_type = $row['method_type'];
    $payment->info_link_creator = $row['info_link_creator'];
    $payment->response_payment_link = $row['response_payment_link'];
    $payment->completed_at = $row['completed_at'];
    $payment->created_at = $row['created_at'];
    return $payment;
  }

  public function sendBiometricValidation(array $signature, array $document): void
  {
    $user = $this->userRepository->getById($document['owner_id']);
    $requireVideo = (bool) $signature['require_video'] ?? null;
    $requireVideoParam = '';
    $fileToUpload = "$this->documentPath/qr";
    if (!file_exists($fileToUpload)) {
      mkdir($fileToUpload, 0700);
    }

    $fileToUploadSigner = $fileToUpload . '/' . $signature['signature_code'] . '.png';

    if (isset($requireVideo) && $requireVideo) {
      $requireVideoParam = '&require_video=true';
    }

    $urlVerify = $_SERVER['DOMAINURL'] . '/?signature_code=' . $signature['signature_code'] . '&document_uuid=' . $document['uuid'] . '&document_id=' . $document['id'] . '&signer_id=' . $signature['id'] . $requireVideoParam;

    $biometryData = [
      'current_step' => BiometryCurrentStep::Email->value,
      'validation_url' => $urlVerify,
      'is_url_active' => (int)true,
      'is_done' => (int)false,
    ];

    $biometryConditions = [
      'document_id' => $document['id'],
      'signer_id' => $signature['id'],
    ];

    $this->biometryRepository->updateByConditions($biometryConditions, $biometryData);
    $this->logger->info(sprintf('Biometry record generated for signer: %s', $signature['id']));
    $qr = new QRCode();
    $qr->render($urlVerify, $fileToUploadSigner);

    $urlQr = $_SERVER['MAINURL'] . '//qr/' . $signature['signature_code'] . '.png';

    $mailMetadata = [
      'urlVerify' => $urlVerify,
      'domainUrl' => $_SERVER['DOMAINURL'],
      'supportEmail' => $_SERVER['SUPPORT_EMAIL'],
      'urlQr' => $urlQr,
      'ownerName' => $user['full_name'],
      'ownerEmail' => $user['email'],
      'documentCode' => $document['document_code'],
    ];

    $subject = 'Usted es integrante del documento ID ' . $document['document_code'];
    $this->mailService->send($signature['email'], $subject, 'verifyIdentity', $mailMetadata);
  }

  public function transformUser(array $row): UserItem
  {
    $user = new UserItem();
    $user->id = $row['id'];
    $user->rfc = $row['rfc'];
    $user->first_name = $row['first_name'];
    $user->last_name = $row['last_name'];
    $user->mother_last_name = $row['mother_last_name'];
    $user->email = $row['email'];
    $user->phone = $row['phone'];
    return $user;
  }
}
