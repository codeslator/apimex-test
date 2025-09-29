<?php

namespace App\Domain\Biometry\Service;

use App\Domain\Biometry\Repository\BiometryRepository;
use App\Domain\Document\Repository\DocumentRepository;
use App\Domain\Signature\Repository\SignatureRepository;
use App\Domain\User\Repository\UserRepository;
use App\Domain\Mail\Service\MailService;
use App\Domain\Biometry\Data\BiometryItem;
use App\Domain\Biometry\Data\BiometryCurrentStep;
use App\Domain\Document\Data\DocumentStatus;
use Ramsey\Uuid\Uuid;
use chillerlan\QRCode\QRCode;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

final class BiometryService
{
  private BiometryRepository $repository;
  private DocumentRepository $documentRepository;
  private SignatureRepository $signatureRepository;
  private UserRepository $userRepository;
  private MailService $mailService;
  private LoggerInterface $logger;
  private string $documentPath = __DIR__ . '/../../../../public/';


  public function __construct(
    BiometryRepository $repository,
    DocumentRepository $documentRepository,
    SignatureRepository $signatureRepository,
    UserRepository $userRepository,
    MailService $mailService,
    LoggerFactory $logger
  ) {
    $this->repository = $repository;
    $this->documentRepository = $documentRepository;
    $this->signatureRepository = $signatureRepository;
    $this->userRepository = $userRepository;
    $this->mailService = $mailService;
    $this->logger = $logger
      ->addFileHandler('biometric_validation.log')
      ->createLogger();
  }

  public function getAll(): array
  {
    return $this->repository->getAll();
  }

  public function create(array $data): void
  {
    $uuid = Uuid::uuid4();
    $biometry = new BiometryItem();
    $biometry->verification_code = $uuid->toString();
    $biometry->document_id = $data['document_id'];
    $biometry->signer_id = $data['signer_id'];
    // $biometry->current_step = $data['current_step'];
    $this->repository->save($biometry);
    $this->logger->info(sprintf('Biometric validation created for signer: %s.', $data['signer_id']));
  }

  public function getById(int $id): BiometryItem
  {
    return $this->transform($this->repository->getById($id));
  }

  public function getBySignatureId(int $signerId): ?BiometryItem
  {
    return $this->transform($this->repository->getBySignatureId($signerId));
  }

  public function deleteByDocumentId(int $documentId): void
  {
    $this->repository->deleteByDocumentId($documentId);
  }
  public function checkValidationsFinishedByDocumentId(int $documentId): bool
  {
    return $this->repository->checkAllValidationFinishedByDocumentId($documentId);
  }

  public function update(array $conditions, array $data): void
  {
    $this->repository->updateByConditions($conditions, $data);
  }

  public function sendBiometricValidation(array $data): void
  {
    $requireVideo = isset($data['require_video']) ? (bool) $data['require_video'] : null;
    $requireVideoParam = '';
    $document = $this->documentRepository->getById($data['document_id']);
    $signature = $this->signatureRepository->getById($data['signer_id']);
    $user = $this->userRepository->getById($document['owner_id']);

    if ($document['status'] == DocumentStatus::SIGNED->value) {
      throw new \DomainException(sprintf('The document is already signed: %s', $document['id']));
    }

    // TODO: Check this validation to send biometric validation
    if ((bool)$signature['is_paid'] && (bool)$signature['is_signed']) {
      throw new \DomainException(sprintf('The document has already been signed by: %s', $signature['signature_code']));
    }
    
    try {
      if (isset($requireVideo) && $requireVideo) {
        $requireVideoParam = '&require_video=true';
      }
      $urlVerify = $_SERVER['DOMAINURL'] . '/?signature_code=' . $signature['signature_code'] . '&document_uuid=' . $document['uuid'] . '&document_id=' . $document['id'] . '&signer_id=' . $signature['id'] . $requireVideoParam;
      
      
      $data = [
        'current_step' => BiometryCurrentStep::Email->value,
        'validation_url' => $urlVerify,
        'is_url_active' => (int)true,
        'is_done' => (int)false,
      ];

      $conditions = [
        'document_id' => $document['id'],
        'signer_id' => $signature['id'],
      ];
      
      $this->repository->updateByConditions($conditions, $data);

      $fileToUpload = "$this->documentPath/qr";
      if (!file_exists($fileToUpload)) {
        mkdir($fileToUpload, 0700);
      }

      $fileToUploadSigner = $fileToUpload . '/' . $signature['signature_code'] . '.png';
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

      $subject = 'Usted es integrante del documento con el ID: ' . $document['document_code'];
      $this->mailService->send($signature['email'], $subject, 'verifyIdentity', $mailMetadata);
      $this->logger->info(sprintf('Biometric validation mail sent to signer: %s.', $signature['email']));
    } catch (\DomainException $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function transform(array $row): BiometryItem
  {
    $biometry = new BiometryItem();
    $biometry->id = $row['id'];
    $biometry->verification_code = $row['verification_code'];
    $biometry->has_photo_identity_uploaded = $row['has_photo_identity_uploaded'];
    $biometry->has_biometric_identity_uploaded = $row['has_biometric_identity_uploaded'];
    $biometry->has_video_identity_uploaded = $row['has_video_identity_uploaded'];
    $biometry->session_id = $row['session_id'];
    $biometry->scan_id = $row['scan_id'];
    $biometry->is_done = $row['is_done'];
    $biometry->validation_url = $row['validation_url'];
    $biometry->is_url_active = $row['is_url_active'];
    $biometry->current_step = $row['current_step'];
    $biometry->completed_at = $row['completed_at'];
    $biometry->created_at = $row['created_at'];
    return $biometry;
  }
}
