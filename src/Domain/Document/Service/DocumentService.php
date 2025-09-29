<?php

namespace App\Domain\Document\Service;

use App\Domain\Document\Repository\DocumentRepository;
use App\Domain\Document\Data\DocumentItem;
use App\Domain\Document\Data\DocumentStatistic;
use App\Domain\User\Data\UserItem;
use App\Domain\File\Data\FileItem;
use App\Domain\Biometry\Data\BiometryItem;
use App\Domain\Signature\Data\SignatureItem;
use App\Domain\Signature\Data\SignatureRole;
use App\Domain\Payment\Data\PaymentItem;
use App\Domain\Document\Data\DocumentStatus;
use App\Domain\Document\Data\DocumentPaymentStatus;
use App\Domain\Document\Utilities\DocumentValidator;
use App\Domain\File\Service\FileService;
use App\Domain\Signature\Service\SignatureService;
use Ramsey\Uuid\Uuid;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;
use DomainException;

final class DocumentService
{

  private DocumentRepository $repository;
  private DocumentValidator $validator;
  private FileService $fileService;
  private SignatureService $signatureService;
  private LoggerInterface $logger;
  private string $documentPath;

  public function __construct(
    DocumentRepository $repository,
    DocumentValidator $validator,
    FileService $fileService,
    SignatureService $signatureService,
    LoggerFactory $loggerFactory
  ) {
    $this->repository = $repository;
    $this->validator = $validator;
    $this->fileService = $fileService;
    $this->signatureService = $signatureService;
    $this->documentPath = dirname(__DIR__, 3) . '/Documents/';
    $this->logger = $loggerFactory
      ->addFileHandler('documents.log')
      ->createLogger();
  }

  public function getAll(array $pagination): array
  {
    $data = $this->repository->getAll($pagination);
    if (count($data['data']) > 0) {
      $rows = [];
      foreach ($data['data'] as $document) {
        $rows[] = $this->transform($document);
      }
      $data['data'] = $rows;
    }
    return $data;
  }

  public function create(array $data, object $loggedUser): int
  {
    $this->validator->validateDocument($data);

    if (count($data['signers']) == 0) {
      throw new DomainException('At least one signer is required to create a document.');
    }

    $this->validator->validateSigners($data);

    // 1. Create the document
    $uuid = Uuid::uuid4();
    $document = new DocumentItem();
    $document->uuid = $uuid->toString();
    $document->document_type_fee_id = 1; // Default to 1 for now
    $document->status = DocumentStatus::CREATED->value; // Initially document status is created
    $document->payment_status = DocumentPaymentStatus::PENDING->value; // And payment status is pending
    $document->owner_id = $loggedUser->id; // Set owner id from authenticated user
    $document->owner_type = $this->getOwnerType($loggedUser); // Set owner type based on user role
    $document->signer_count = count($data['signers']);
    $id = $this->repository->save($document);

    $this->logger->info(sprintf('Document created with uuid: %s', $uuid->toString()));

    // Set values to data array
    $data['document_id'] = $id;
    $data['uuid'] = $uuid->toString();
    $data['owner_id'] = $loggedUser->id;
    $data['document_type_fee_id'] = $document->document_type_fee_id;

    // 2. Create the file form document
    $this->fileService->create($data);
    $this->logger->info(sprintf('File record created for document with uuid: %s', $uuid->toString()));
    // 3. Create signers from document, create payment and generate biometric register
    $this->signatureService->createFromDocument($data);
    return (int) $id;
  }

  public function getById(int|string $identifier): ?DocumentItem
  {
    $document = ($this->isUuid($identifier)) ? $this->repository->getByUuid((string) $identifier) : $this->repository->getById((int) $identifier);
    if ($document) {
      return $this->transform($document);
    }
    return $document;
  }

  public function getAllByOwnerId(int $ownerId, array $pagination, object $loggedUser): array
  {
    if ($loggedUser->id !== $ownerId && $loggedUser->role->name !== 'ADMIN') {
      throw new DomainException('Access denied.');
    }
    $data = $this->repository->getAllByOwnerId($ownerId, $pagination);
    if (count($data['data']) > 0) {
      $rows = [];
      foreach ($data['data'] as $document) {
        $rows[] = $this->transform($document);
      }
      $data['data'] = $rows;
    }
    return $data;
  }

  public function getStatisticByOwnerId(int $ownerId): DocumentStatistic
  {
    $data = $this->repository->getDocumentTotalsByOwnerId($ownerId);
    $statistic = new DocumentStatistic();
    $statistic->total = (int) $data['total'];
    $statistic->created = (int) $data['created'];
    $statistic->pending = (int) $data['pending'];
    $statistic->signed = (int) $data['signed'];
    return $statistic;
  }


  public function delete(int $id): void
  {
    $document = $this->repository->getById($id);
    $this->logger->info(sprintf('Deleting document with uuid: %s', $document['uuid']));

    $status = [
      DocumentStatus::CREATED->value,
      DocumentStatus::SIGNED_PENDING->value,
      DocumentStatus::REVIEW->value,
      DocumentStatus::REJECTED->value
    ];

    if (!in_array($document['status'], $status)) {
      throw new DomainException(sprintf('You can only delete documents if the status is CREATED, SIGNED_PENDING, REVIEW or REJECTED. Current status: %s', $document['status']));
    }

    if($document['status'] == DocumentStatus::DELETED->value || $document['is_deleted'] == true) {
      throw new DomainException(sprintf('The document %s was deleted before, you can\'t delete it again.', $document['uuid']));
    }
    
    $filePath = $this->documentPath . $document['uuid'];
    if ($this->signatureService->recursiveDelete($filePath)) {
      $this->logger->info(sprintf('Deleting (change status) document: %s', $document['uuid']));
      $data = [
        'status' => DocumentStatus::DELETED->value,
        'is_deleted' => true,
        'deleted_at' => date('Y-m-d H:i:s'),
      ];
      $this->repository->delete($id, $data);
      $this->signatureService->deleteAllByDocumentId($document['id']);
      $this->logger->info(sprintf('Document %s deleted successfully.', $document['uuid']));
    } else {
      throw new DomainException(sprintf('The document %s could not be deleted, please contact support.', $document['uuid']));
    }
  }

  public function update(int $id, array $data): void
  {
    $this->repository->update($id, $data);
  }

  public function transform(array $row): DocumentItem
  {
    $document = new DocumentItem();
    $document->id = $row['id'];
    $document->uuid = $row['uuid'];
    $document->document_code = $row['document_code'];
    $document->status = $row['status'];
    $document->payment_status = $row['payment_status'];
    $document->owner = $this->transformOwner($this->repository->getOwnerForDocument((int)$row['owner_id']));
    $document->signatures = $this->transformSignatures($this->repository->getSignaturesForDocument((int)$row['id']));
    $document->file = $this->transformFile($this->repository->getFileForDocument((int)$row['id']));
    $document->owner_type = $row['owner_type'];
    $document->is_deleted = $row['is_deleted'];
    $document->created_at = $row['created_at'];
    $document->signed_at = $row['signed_at'];
    $document->deleted_at = $row['deleted_at'];
    return $document;
  }

  public function transformOwner(array $row): UserItem
  {
    $user = new UserItem();
    $user->id = $row['id'];
    $user->uuid = $row['uuid'];
    $user->rfc = $row['rfc'];
    $user->first_name = $row['first_name'];
    $user->last_name = $row['last_name'];
    $user->mother_last_name = $row['mother_last_name'];
    $user->email = $row['email'];
    $user->phone = $row['phone'];
    $user->role = $row['role_id'];
    $user->created_at = $row['created_at'];
    return $user;
  }

  public function transformSignatures(array $rows): array
  {
    $signatures = [];
    foreach ($rows as $row) {
      $signature = new SignatureItem();
      $signature->id = $row['id'];
      $signature->uuid = $row['uuid'];
      $signature->document_id = $row['document_id'];
      $signature->signature_code = $row['signature_code'];
      $signature->rfc = $row['rfc'];
      $signature->curp = $row['curp'];
      $signature->first_name = $row['first_name'];
      $signature->last_name = $row['last_name'];
      $signature->mother_last_name = $row['mother_last_name'];
      $signature->birth_date = $row['birth_date'];
      $signature->email = $row['email'];
      $signature->role = $row['role'];
      $signature->portion = $row['portion'];
      $signature->payment = $row['payment'];
      $signature->iva_pay = $row['iva_pay'];
      $signature->total_pay = $row['total_pay'];
      $signature->is_paid = $row['is_paid'];
      $signature->is_prepaid = $row['is_prepaid'];
      $signature->is_signed = $row['is_signed'];
      $signature->signature_page = $row['signature_page'];
      $signature->posX = $row['posX'];
      $signature->posY = $row['posY'];
      $signature->require_video = $row['require_video'];
      $biometricValidation = $this->repository->getBiometryBySignatureId((int)$row['id']);
      $signature->biometric_validation = $biometricValidation ? $this->transformBiometricValidation($biometricValidation) : null;
      if ($row['role'] != SignatureRole::SIGNER->value && (bool)!$row['is_prepaid']) {
        $signature->payment_data = $this->transformPayment($this->repository->getPaymentBySignatureId((int)$row['id']));
      }
      $signature->created_at = $row['created_at'];
      $signature->signed_at = $row['signed_at'];
      $signatures[] = $signature;
    }
    return $signatures;
  }

  public function transformFile(array $row): FileItem
  {
    $file = new FileItem();
    $file->id = $row['id'];
    $file->code = $row['code'];
    $file->name = $row['name'];
    $file->document_id = $row['document_id'];
    return $file;
  }

  public function transformBiometricValidation(array $row): BiometryItem
  {
    $biometry = new BiometryItem();
    $biometry->id = $row['id'];
    $biometry->document_id = $row['document_id'];
    $biometry->signer_id = $row['signer_id'];
    $biometry->verification_code = $row['verification_code'];
    $biometry->has_photo_identity_uploaded = $row['has_photo_identity_uploaded'];
    $biometry->has_biometric_identity_uploaded = $row['has_biometric_identity_uploaded'];
    $biometry->has_video_identity_uploaded = $row['has_video_identity_uploaded'];
    $biometry->is_done = $row['is_done'];
    $biometry->validation_url = $row['validation_url'];
    $biometry->is_url_active = $row['is_url_active'];
    $biometry->current_step = $row['current_step'];
    $biometry->completed_at = $row['completed_at'];
    $biometry->created_at = $row['created_at'];
    return $biometry;
  }
  public function transformPayment(array $row): PaymentItem
  {
    $payment = new PaymentItem();
    $payment->id = $row['id'];
    $payment->signer_id = $row['signer_id'];
    $payment->invoice_id = $row['invoice_id'];
    $payment->payment_link = $row['payment_link'];
    $payment->status = $row['status'];
    $payment->method_type = $row['method_type'];
    $payment->created_at = $row['created_at'];
    return $payment;
  }

  public function isUuid(mixed $uuid): bool
  {
    if (!is_string($uuid) || (preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', $uuid) !== 1)) {
      return false;
    }
    return true;
  }

  public function getOwnerType(object $user): string
  {
    return $user->role->code === 'USER' ? 'NATURAL' : 'LEGAL';
  }
}
