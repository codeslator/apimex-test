<?php

namespace App\Domain\Signature\Service;

use App\Domain\Signature\Repository\SignatureRepository;
use App\Domain\Signature\Repository\AutoSignRepository;
use App\Domain\DocumentTypeFee\Service\DocumentTypeFeeService;
use App\Domain\Signature\Data\SignatureItem;
use App\Domain\Signature\Data\SignatureRole;
use App\Domain\Document\Data\DocumentStatus;
use App\Domain\Signature\Utilities\SignatureValidator;
use App\Domain\Payment\Repository\GigStackRepository;
use App\Domain\Payment\Service\PaymentService;
use App\Domain\Biometry\Service\BiometryService;
use App\Domain\Document\Repository\DocumentRepository;
use App\Domain\File\Repository\FileRepository;
use App\Domain\User\Repository\UserRepository;
use App\Domain\SignatureCredit\Repository\SignatureCreditRepository;
use App\Domain\SignatureInventory\Repository\SignatureInventoryRepository;
use App\Domain\Document\Data\DocumentPaymentStatus;
use App\Domain\SignatureInventory\Data\SignatureInventorySource;
use App\Domain\Mail\Service\MailService;
use App\Domain\File\Data\FileItem;
use App\Domain\Biometry\Data\BiometryCurrentStep;
use Ramsey\Uuid\Uuid;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;
use App\Traits\AmazonS3Trait;

final class SignatureService
{

  private SignatureRepository $repository;
  private AutoSignRepository $autoSignRepository;
  private SignatureValidator $validator;
  private DocumentTypeFeeService $documentTypeFeeService;
  private PaymentService $paymentService;
  private BiometryService $biometryService;
  private DocumentRepository $documentRepository;
  private FileRepository $fileRepository;
  private UserRepository $userRepository;
  private SignatureCreditRepository $creditRepository;
  private SignatureInventoryRepository $inventoryRepository;
  private GigStackRepository $gigStackRepository;
  private MailService $mailService;
  private LoggerInterface $logger;
  private string $documentPath;

  use AmazonS3Trait;

  private $positionSignatures = [
    "0" => ['PosX' => 1, 'PosY' => 680],
    "1" => ['PosX' => 130, 'PosY' => 680],
    "2" => ['PosX' => 260, 'PosY' => 680],
    "3" => ['PosX' => 390, 'PosY' => 680],
    "4" => ['PosX' => 1, 'PosY' => 610],
    "5" => ['PosX' => 130, 'PosY' => 610],
    "6" => ['PosX' => 260, 'PosY' => 610],
    "7" => ['PosX' => 390, 'PosY' => 610],
    "8" => ['PosX' => 1, 'PosY' => 540],
    "9" => ['PosX' => 130, 'PosY' => 540],
  ];

  public function __construct(
    SignatureRepository $repository,
    AutoSignRepository $autoSignRepository,
    SignatureValidator $validator,
    DocumentTypeFeeService $documentTypeFeeService,
    PaymentService $paymentService,
    BiometryService $biometryService,
    DocumentRepository $documentRepository,
    FileRepository $fileRepository,
    UserRepository $userRepository,
    SignatureCreditRepository $creditRepository,
    SignatureInventoryRepository $inventoryRepository,
    GigStackRepository $gigStackRepository,
    MailService $mailService,
    LoggerFactory $loggerFactory
  ) {
    $this->repository = $repository;
    $this->autoSignRepository = $autoSignRepository;
    $this->validator = $validator;
    $this->documentTypeFeeService = $documentTypeFeeService;
    $this->paymentService = $paymentService;
    $this->biometryService = $biometryService;
    $this->documentRepository = $documentRepository;
    $this->fileRepository = $fileRepository;
    $this->userRepository = $userRepository;
    $this->creditRepository = $creditRepository;
    $this->inventoryRepository = $inventoryRepository;
    $this->gigStackRepository = $gigStackRepository;
    $this->mailService = $mailService;
    $this->documentPath = dirname(__DIR__, 3) . '\Documents';
    $this->logger = $loggerFactory
      ->addFileHandler('document_signatures.log')
      ->createLogger();
  }

  public function getAll(): array
  {
    return $this->repository->getAll();
  }

  public function create(array $data): void
  {
    $documentTypeFee = $this->documentTypeFeeService->getById((int)$data['document_type_fee_id']);

    $uuid = Uuid::uuid4();
    $signature = new SignatureItem();
    $signature->signature_code = uniqid('SGN');
    $signature->uuid = $uuid->toString();
    $signature->document_id = $data['document_id'];
    $signature->rfc = $data['rfc'];
    $signature->first_name = $data['first_name'];
    $signature->last_name = $data['last_name'];
    $signature->mother_last_name = $data['mother_last_name'];
    $signature->signer_type = $data['signer_type'];
    $signature->role = $data['role'];
    $signature->portion = $data['portion'];
    $signature->total_pay = $documentTypeFee->total;
    $signature->iva_pay = $documentTypeFee->amount_iva;
    $signature->payment = $documentTypeFee->total - $documentTypeFee->amount_iva;
    $position = $this->positionSignatures[0];
    $signature->is_paid = $data['is_paid'];
    $signature->is_prepaid = $data['is_prepaid'];
    $signature->posX = isset($position['PosX']) ?? 0;
    $signature->posY = isset($position['PosY']) ?? 0;

    $this->repository->save($signature);
  }

  public function createFromDocument(array $data): void
  {
    $hasCredits = false;
    $signatureQuantity = count(array_filter($data['signers'], array($this, 'filter_signers'))); // Filtra solo los firmantes o pagadores/firmantes
    $this->validator->validateSigners($data);
    // Get user data  by owner id
    $user = $this->userRepository->getById((int)$data['owner_id']);
    if (!empty($user['signature_credit_id'])) {
      $credit = $this->creditRepository->getById((int)$user['signature_credit_id']);
      $hasCredits = true;
      if (((int)$credit['remaining_quantity'] <= 0) || ($signatureQuantity > (int)$credit['remaining_quantity'])) {
        $hasCredits = false;
      }
    }
    $documentTypeFee = $this->documentTypeFeeService->getById((int)$data['document_type_fee_id']);
    try {
      $this->repository->pdo->beginTransaction(); // Begin transaction
      foreach ($data['signers'] as $key => $signer) {
        $totalPay = $documentTypeFee->amount * $signatureQuantity;
        $totalIva = $totalPay * $documentTypeFee->iva;
        $uuid = Uuid::uuid4();
        $signatureCode = uniqid('SGN');
        $signature = new SignatureItem();
        $signature->signature_code = $signatureCode;
        $signature->uuid = $uuid->toString();
        $signature->document_id = $data['document_id'];
        $signature->rfc = $signer['rfc'];
        $signature->first_name = $signer['first_name'];
        $signature->last_name = $signer['last_name'];
        $signature->mother_last_name = $signer['mother_last_name'];
        $signature->email = $signer['email'];
        $signature->signer_type = $signer['signer_type'];
        $signature->role = $signer['role'];
        $signature->signature_page = $data['page_sign'];
        $signature->portion = ($signer['role'] != SignatureRole::SIGNER->value) ? $signer['portion'] : 0;
        $signature->total_pay = ($signer['role'] != SignatureRole::SIGNER->value) ? $totalPay  : $documentTypeFee->amount;
        $signature->iva_pay = ($signer['role'] != SignatureRole::SIGNER->value) ? $totalIva : $documentTypeFee->amount_iva;
        $signature->payment = $totalPay + $totalIva;
        $signature->is_paid = $hasCredits ? true : false;
        $signature->is_prepaid = $hasCredits ? true : false;
        $signature->is_signed = false;
        $signature->require_video = isset($signer['require_video']) ? $signer['require_video'] : null;
        $position = $this->positionSignatures[$key];
        $signature->posX = isset($position['PosX']) ? $position['PosX'] : 0;
        $signature->posY = isset($position['PosY']) ? $position['PosY'] : 0;
        $id = $this->repository->save($signature);

        if ($signer['role'] != SignatureRole::SIGNER->value && !$hasCredits) { // If person is not signer (is payer or both) and user has not credits
          // Create array to generate invoice
          $invoiceData = [
            'full_name' => $signer['first_name'] . ' ' . $signer['last_name'] . ' ' . $signer['mother_last_name'],
            'email' => $signer['email'],
            'item_name' => "Firma de Documento: ($signatureQuantity) Firmantes",
            'item_price' => $documentTypeFee->amount * $signatureQuantity,
            'item_tax' => $documentTypeFee->iva,
            'document_id' => $data['document_id'],
            'signer_id' => $id
          ];
          $invoice = $this->gigStackRepository->generateInvoice($invoiceData);
          $response = $this->gigStackRepository->generatePaymentLink($invoice);

          $price = $documentTypeFee->amount * $signatureQuantity;
          $iva = $price * $documentTypeFee->iva;

          $paymentData = [
            'signer_id' => $id,
            'payment_link' => $response->data->shortURL,
            'invoice_id' => $response->data->fid,
            'method_type' => $response->data->custom_method_types[0]->id,
            'info_link_creator' => json_encode($response),
            'amount' => $price + $iva,
            'payer_id' => $user['id'],
          ];
          // Create payment after generate invoice and signature
          $this->paymentService->create($paymentData);
          $this->logger->info(sprintf('Payment generated for document: %s', $data['document_id']));
        }

        if ($signer['role'] != SignatureRole::PAYER->value) {
          $biometryData = [
            'document_id' => $data['document_id'],
            'signer_id' => $id,
            'require_video' => $signer['require_video']
          ];
          // Then create biometric validation register
          $this->biometryService->create($biometryData);
          $this->logger->info(sprintf('Biometry record generated for signer: %s', $id));
        }

        if ($hasCredits) { // If has credits, decrement from user credit
          $remainingQuantity = $credit['remaining_quantity'] - $signatureQuantity;
          $consumedQuantity = $credit['consumed_quantity'] + $signatureQuantity;
          $creditData = [
            'remaining_quantity' => $remainingQuantity,
            'consumed_quantity' => $consumedQuantity,
          ];
          $this->creditRepository->updateById((int)$credit['id'], $creditData);

          if ($signer['role'] != SignatureRole::PAYER->value) {
            $this->biometryService->sendBiometricValidation($biometryData);
          }

          $documentData = [
            'status' => DocumentStatus::SIGNED_PENDING->value,
            'payment_status' => DocumentPaymentStatus::PAIDOUT->value
          ];
          $this->documentRepository->update($data['document_id'], $documentData); // Update document status
          $this->logger->info(sprintf('Signatures for document with id [%s] created successfully.', $data['document_id']));
        } else { // Else decrement from global inventory
          $inventory = $this->inventoryRepository->getBySource(SignatureInventorySource::PSC_WORLD->value);
          $inventoryRemainingQuantity = $inventory['quantity'] - $signatureQuantity;
          $inventoryData = [
            'quantity' => $inventoryRemainingQuantity
          ];
          $this->inventoryRepository->updateById((int)$inventory['id'], $inventoryData); // Update signatur global stock
        }
      }
      $this->repository->pdo->commit(); // Commit transaction
    } catch (\Exception $e) {
      $this->repository->pdo->rollBack(); // Rollback transaction on error
      throw new \DomainException($e->getMessage());
    }
  }

  private function filter_signers($signer)
  {
    return $signer['role'] != SignatureRole::PAYER->value;
  }

  private function getPayerByDocumentId(int $documentId)
  {
    $roles = [
      SignatureRole::PAYER->value,
      SignatureRole::SIGNER_PAYER->value,
    ];
    $payer = $this->repository->getAllByDocumentIdAndRole($documentId, $roles);
    return $payer[0];
  }

  public function getById(int $id): SignatureItem
  {
    return $this->transform($this->repository->getById($id));
  }

  // TODO: Refactorizar funcion para firmar documentos
  public function signDocument(array $data): mixed
  {
    try {
      $this->logger->info(sprintf('Document to sign: %s', $data['document_id']));
      $this->logger->info(sprintf('Signer: %s', $data['id']));
      $this->repository->pdo->beginTransaction(); // Begin transaction
      $document = $this->documentRepository->getById((int)$data['document_id']); //Get the document
      $signature = $this->getById((int)$data['id']); // Get the signer

      $biometry = $this->biometryService->getBySignatureId((int)$data['id']); // Get the biometry data related to the signer
      $this->logger->info(sprintf('Biometry data by signer: %s', json_encode($biometry)));

      $file = $this->fileRepository->getByDocumentId((int)$data['document_id']); // Get the file related to the document
      $fileToUpload = "$this->documentPath/" . $document['uuid']; // Directory to upload the document
      $fileName = $document['uuid'] . ".pdf";
      $documentPath = $this->documentPath . $file['url'];

      if ($document['payment_status'] == DocumentPaymentStatus::PENDING->value) {
        throw new \DomainException(sprintf('The document is pending payment:  %s', $document['id']));
      }

      if ($signature->is_signed) {
        throw new \DomainException(sprintf('The document has already been signed by:  %s', $signature->signature_code));
      }

      if ($signature->role == SignatureRole::PAYER->value) {
        throw new \DomainException(sprintf('This member of the document is not a signer:  %s', $signature->signature_code));
      }

      // --------------------------------- PRIMERO, CAMBIAR ESTADOS DE LA FIRMA, BIOMETRIA Y DEL DOCUMENTO

      $signatureDate = [
        'is_signed' => true,
        'signed_at' => date('Y-m-d H:i:s'),
      ];

      $this->repository->updateById($signature->id, $signatureDate); // Cambia el estado de la firma solamente y mantiene los datos iniciales

      $biometryData = [
        'current_step' => BiometryCurrentStep::Finish->value,
        'is_url_active' => (int)false,
      ];

      $conditions = [
        'id' => $biometry->id,
        'document_id' => $document['id'],
        'signer_id' => $signature->id,
      ];

      $this->biometryService->update($conditions, $biometryData);
      $this->logger->info(sprintf('Biometry completed for signer: %s', $signature->email));

      // --------------------------------- SEGUNDO, CONSOLIDAR EL DOCUMENTO CON LOS CERTIFICADOS DE BIOMETRIA

      $converted = $this->mergeDocument($fileToUpload, $documentPath, $signature->signature_code);

      $mergedName = $document['uuid'] . "_merged.pdf";
      $mergedPath = "$fileToUpload/$mergedName";
      file_put_contents($mergedPath, $converted); // Mergea el documento con los certificados de biometria


      $mergeUrl = $this->putObject($mergedPath, $fileName);

      $this->fileRepository->updateByFileName(
        $fileName,
        [
          'url' => $document['uuid'] . "/" . $mergedName,
          'remote_url' => $mergeUrl
        ]
      );
      $this->logger->info("Document merged successfully.");

      // --------------------------------- ULTIMO, CONSOLIDAR EL DOCUMENTO CON LOS CERTIFICADOS DE BIOMETRIA

      $signatures = $this->repository->getAllByDocumentId($document['id']); // Obtiene todas las firmas por el document_id relacionado

      $allValuesColumns = array_column($signatures, 'is_signed'); // Obtiene todos los estados de las firmas para filtrar
      $inArray = in_array(false, $allValuesColumns); // Verifica si hay alguna firma por completar, retorna verdadero si hay alguna firma sin completar

      if (!$inArray) {
        $this->logger->info("All signatures for document [" . $document['id'] . "] with uuid [" . $document['uuid'] . "] are completed!");
        $file = $this->fileRepository->getByDocumentId((int)$data['document_id']);

        $dataSigners = [];
        $documentBase64 = base64_encode(file_get_contents($this->documentPath . $file['url']));

        foreach ($signatures as $signature) {
          $signerData = [
            'Nombres' => $signature['first_name'],
            'ApPaterno' => $signature['last_name'],
            'ApMaterno' => $signature['mother_last_name'],
            'Email' => $signature['email'],
            'RFC' => $signature['rfc'],
            'NombreDocumento' => $document['uuid'] . ".pdf",
            'Identificacion' => '',
            'Leyenda' => '',
            'NumeroIdentificacion' => '',
            'Imagen' => '',
            'Pagina' => $signature['signature_page'],
            'PosX' => ($signature['posX'] != 0) ? $signature['posX'] : 100,
            'PosY' => ($signature['posY'] != 0) ? $signature['posY'] : 200,
            'Ubicacion' => ''
          ];
          array_push($dataSigners, $signerData);
        }

        $response = $this->autoSignRepository->signDocument(
          $document['uuid'],
          $dataSigners,
          $documentBase64
        );

        if ($response['status'] == 'success') {
          $this->logger->info(sprintf('Document signed: %s', $response['documentName']));
          $this->logger->info("Document signed successfully.");

          if ($this->biometryService->checkValidationsFinishedByDocumentId($document['id'])) { // Si las validaciones biometricas estan completadas
            $documentData = [
              'status' => DocumentStatus::SIGNED->value,
              'signed_at' => date('Y-m-d H:i:s'),
            ];
            $this->documentRepository->update($document['id'], $documentData); // Cambia el estado del documento a SIGNED (firmado)
          }

          $zipFile = new FileItem();
          $zipFile->code = uniqid();
          $zipFile->document_id = $document['id'];
          $zipFile->signer_id = null;
          $zipFile->name = $response['zipName'];
          $zipFile->url = "$fileToUpload/" . $response['zipName'];
          $zipFile->remote_url = $response['zipUrl'];
          $this->fileRepository->save($zipFile);

          $this->fileRepository->updateByFileName(
            $fileName,
            [
              'url' => $document['uuid'] . "/" . $response['documentName'],
              'remote_url' => $response['documentUrl']
            ]
          );

          foreach ($signatures as $signer) { // Recorrer a todos los participantes para enviar el documento
            $this->mailService->send(
              $signer['email'],
              '¡El documento se ha firmado éxitosamente!',
              'document_signed',
              [
                'fullName' => $signer['first_name'] . ' ' . $signer['last_name']  . ' ' . $signer['mother_last_name'],
                'documentCode' => $document['document_code'],
                'domainUrl' => $_SERVER['DOMAINURL'],
                'supportEmail' => $_SERVER['SUPPORT_EMAIL']
              ],
              [
                [
                  'filePath' => $this->getDocumentLink($response['zipName']),
                  'filename' => $response['zipName']
                ]
              ]
            );
          }
          $this->logger->info("Document signed was sent to all participants.");

          $this->recursiveDelete($fileToUpload);
          $this->logger->info(sprintf('Directory [%s] and files deleted successfully!', $document['uuid']));
          $this->repository->pdo->commit();
        }
        return $response['status'];
      }
      $this->logger->info(sprintf('Some signatures are pending for document [%s].', $document['document_code']));
      $this->repository->pdo->commit();
      return 'success'; // Si hay alguna firma por completar, retorna success
    } catch (\Exception $e) {
      $this->logger->error(sprintf('Error signing document: %s', $e->getMessage()));
      $this->repository->pdo->rollBack();
      throw new \DomainException($e->getMessage());
    }
  }

  public function deleteByDocumentId(int $documentId): void
  {
    $payer = $this->getPayerByDocumentId($documentId);
    $this->paymentService->deleteBySignerId($payer['id']);
    $this->repository->deleteByDocumentId($documentId);
  }

  public function mergeDocument(string $filePath, string $documentPath, string $signatureCode): mixed
  {
    $optionsFiles = [];
    $optionDocumentFile = [
      'name' => 'files',
      'contents' => Utils::tryFopen($documentPath, 'r'),
      'headers'  => [
        'Content-Type' => '<Content-type header>'
      ]
    ];

    array_push($optionsFiles, $optionDocumentFile);

    if (file_exists("$filePath/$signatureCode")) {
      if (file_exists("$filePath/$signatureCode/signed.pdf")) {
        $this->logger->info(sprintf('The current signature file: %s', "$filePath/$signatureCode/signed.pdf"));

        $optionSignatureFile = [
          'name' => 'files',
          'contents' => Utils::tryFopen("$filePath/$signatureCode/signed.pdf", 'r'),
          'filename' => "$signatureCode.pdf",
          'headers'  => [
            'Content-Type' => '<Content-type header>'
          ]
        ];
        array_push($optionsFiles, $optionSignatureFile);
      }
    }

    $clientGuzzle = new Client();
    $headersGuzzle = [
      'Uuid' => $_SERVER['DOC_CONSOLIDADOR_UUID'],
    ];

    $requestGuzzle = new Request('POST', $_SERVER['URL_CONSOLIDADOR'], $headersGuzzle);

    $resGuzzle = $clientGuzzle->sendAsync($requestGuzzle, [
      'multipart' => $optionsFiles
    ])->wait();

    return $resGuzzle->getBody();
  }

  public function transform(array $row): SignatureItem
  {
    $signature = new SignatureItem();
    $signature->id = $row['id'];
    $signature->uuid = $row['uuid'];
    $signature->document_id = $row['document_id'];
    $signature->signature_code = $row['signature_code'];
    $signature->is_signed = $row['is_signed'];
    $signature->rfc = $row['rfc'];
    $signature->first_name = $row['first_name'];
    $signature->last_name = $row['last_name'];
    $signature->mother_last_name = $row['mother_last_name'];
    $signature->email = $row['email'];
    $signature->role = $row['role'];
    $signature->portion = $row['portion'];
    $signature->payment = $row['payment'];
    $signature->is_paid = $row['is_paid'];
    $signature->is_prepaid = $row['is_prepaid'];
    $signature->require_video = $row['require_video'];
    $signature->signature_page = $row['signature_page'];
    $signature->posX = $row['posX'];
    $signature->posY = $row['posY'];
    $signature->created_at = $row['created_at'];
    $signature->signed_at = $row['signed_at'];
    return $signature;
  }

  public function discountCredits(int $userId, int $quantity): void
  {
    // if ($hasCredits) { // If has credits, decrement from user credit
    //   $remainingQuantity = $credit['remaining_quantity'] - $signatureQuantity;
    //   $consumedQuantity = $credit['consumed_quantity'] + $signatureQuantity;
    //   $creditData = [
    //     'remaining_quantity' => $remainingQuantity,
    //     'consumed_quantity' => $consumedQuantity,
    //   ];
    //   $this->creditRepository->updateById((int)$credit['id'], $creditData);
    // } else { // Else decrement from global inventory
    //   $inventory = $this->inventoryRepository->getBySource(SignatureInventorySource::PSC_WORLD->value);
    //   $inventoryRemainingQuantity = $inventory['quantity'] - $signatureQuantity;
    //   $inventoryData = [
    //     'quantity' => $inventoryRemainingQuantity
    //   ];
    //   $this->inventoryRepository->updateById((int)$inventory['id'], $inventoryData); // Update signatur global stock
    // }
  }

  public function recursiveDelete(string $filePath): bool
  {
    $this->logger->info(sprintf('Deleting path: %s', $filePath));
    if (is_file($filePath)) {
      return @unlink($filePath);
    } elseif (is_dir($filePath)) {
      $scan = glob(rtrim($filePath, '/') . '/*');
      foreach ($scan as $index => $path) {
        $this->recursiveDelete($path);
      }
      return @rmdir($filePath);
    }
    $this->logger->info(sprintf('Path not found: %s', $filePath));
    return false;
  }

  public function deleteAllByDocumentId(int $documentId): void
  {
    $conditions = [
      'document_id' => $documentId,
    ];
    $data = [
      'is_signed' => false,
      'signed_at' => null,
    ];
    $this->repository->deleteByConditions($conditions, $data);
  }
}
