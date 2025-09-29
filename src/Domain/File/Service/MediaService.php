<?php

namespace App\Domain\File\Service;

use App\Domain\File\Repository\FileRepository;
use App\Domain\File\Data\FileItem;
use App\Domain\Signature\Repository\SignatureRepository;
use App\Domain\Document\Repository\DocumentRepository;
use App\Domain\Biometry\Repository\BiometryRepository;
use App\Domain\Biometry\Data\BiometryCurrentStep;
use App\Domain\File\Utilities\FileValidator;
use App\Domain\Signature\Utilities\SignatureValidator;
use App\Factory\LoggerFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use Psr\Log\LoggerInterface;
use DomainException;
use ZipArchive;
use App\Traits\AmazonS3Trait;

final class MediaService
{
  private FileRepository $repository;
  private SignatureRepository $signatureRepository;
  private DocumentRepository $documentRepository;
  private BiometryRepository $biometryRepository;
  private FileValidator $validator;
  private SignatureValidator $signatureValidator;
  private LoggerInterface $logger;
  use AmazonS3Trait;

  private string $documentPath = __DIR__ . '/../../../Documents/';

  public function __construct(
    FileRepository $repository,
    SignatureRepository $signatureRepository,
    DocumentRepository $documentRepository,
    BiometryRepository $biometryRepository,
    FileValidator $validator,
    SignatureValidator $signatureValidator,
    LoggerFactory $logger
  ) {
    $this->repository = $repository;
    $this->signatureRepository = $signatureRepository;
    $this->documentRepository = $documentRepository;
    $this->biometryRepository = $biometryRepository;
    $this->validator = $validator;
    $this->signatureValidator = $signatureValidator;
    $this->logger = $logger
      ->addFileHandler('media.log')
      ->createLogger();
  }

  public function uploadValidationFiles(array $data): void
  {
    $this->validator->validateVideo($data);
    error_reporting(E_ALL ^ E_WARNING);
    $hasVideo = isset($data['files']['video']);
    $document = $this->documentRepository->getById($data['document_id']);
    $signature = $this->signatureRepository->getByDocumentAndSignatureIds($data['signer_id'], $data['document_id']);
    $this->logger->info(sprintf('Uploading media biometry files for document with uuid: %s', $document['uuid']));

    if (empty($signature)) {
      throw new DomainException('The signer not found');
    }

    if ($signature['is_signed']) {
      throw new DomainException('This signature has already signed the contract!');
    }

    // TODO: Uncomment this block when the signature mapping with document data is implemented.
    // $mappedData = [];
    // $this->logger->info(sprintf('Data: %s', json_encode( $data['files']['data'])));
    // if (isset($data['files']['data'])) {
    //   $mappedData = $this->signatureValidator->mapDocumentData($data['files']['data']); // Mapea los datos obtenidos desde la validacion biometrica
    //   $this->logger->info(sprintf('Mapped data: %s', json_encode($mappedData)));
    // }

    // if (!empty($mappedData)) {
    //   $this->signatureRepository->updateById($signature['id'], $mappedData); // Cambia el estado de la firma con los datos mapeados
    // }

    $signature = $this->signatureRepository->getById($signature['id']); // ? Get the signature again to get the updated data.

    $this->logger->info(sprintf('Signer %s uploading biometry files', $signature['email']));

    $certificateTemplatePath = $this->documentPath . 'validation_template.docx';
    $fileToUpload = "$this->documentPath/" . $document['uuid'];
    $fileToUploadSigner = "$fileToUpload/" . $signature['signature_code'];
    $documentName = "$fileToUploadSigner/signed.docx";
    $pdfName = 'signed.pdf';
    $pdfPath = "$fileToUploadSigner/$pdfName";

    if (!file_exists($fileToUpload)) {
      mkdir($fileToUpload, 0700);
    }

    if (!file_exists($fileToUploadSigner)) {
      mkdir($fileToUploadSigner, 0700);
    }

    try {
      $this->repository->pdo->beginTransaction();
      $fileImgIdentityFront = null;
      $fileImgIdentityBack = null;
      $genericPlaceholder = "$this->documentPath" . 'genericplaceholder.png';
      if (isset($data['files']['biometry']['front_image'])) {
        $fileImgIdentityFront = $this->saveFile64($data['files']['biometry']['front_image'], $fileToUploadSigner, 'imgIdentityFront');
        $this->logger->info(sprintf('Biometry Front Image for signer with code (%s) processed successfully.', $signature['signature_code']));
      }
      if (isset($data['files']['biometry']['back_image'])) {
        $fileImgIdentityBack = $this->saveFile64($data['files']['biometry']['back_image'], $fileToUploadSigner, 'imgIdentityBack');
        $this->logger->info(sprintf('Biometry Back Image for signer with code (%s) processed successfully.', $signature['signature_code']));
      }

      $this->logger->info(sprintf('Biometric files for signer (%s) processed successfully.', $signature['email']));

      // Biometric Validation Certificate
      $this->logger->info(sprintf('Generating biometric validation certificated for signer: %s', $signature['email']));

      if ($hasVideo) { // genera Sello TSA con el Video
        $fileVideo = $this->saveFile64($data['files']['video'], $fileToUploadSigner, uniqid('file'), 'file');
        $this->generateTSA($fileVideo['path'], $fileToUploadSigner);
        $this->logger->info(sprintf('Biometry Video (%s) processed successfully.', $signature['signature_code']));
      } else { // genera Sello TSA con la identificacion frontal
        $this->generateTSA($fileImgIdentityFront['path'], $fileToUploadSigner);
        $this->logger->info(sprintf('Biometry Front Image (%s) processed successfully.', $signature['signature_code']));
      }


      $fullName = $signature['first_name'] . ' ' . $signature['last_name'] . ' ' . $signature['mother_last_name'];
      $birthDate = isset($signature['birth_date']) ? \DateTime::createFromFormat('Y-m-d', $signature['birth_date'])->format('d/m/Y') : '';
      $certicateData = [
        'first_name' => $signature['first_name'],
        'last_name' => $signature['last_name'],
        'mother_last_name' => $signature['mother_last_name'],
        'full_name' => $fullName,
        'email' => $signature['email'],
        'curp' => $signature['curp'] ?? '',
        'rfc' => $signature['rfc'] ?? '',
        'birth_date' => $birthDate,
        'generated_at' => date('d/m/Y'),
        'front_image' => [
          'path' => $fileImgIdentityFront['path'] ?? $genericPlaceholder,
          'width' => 300,
          'height' => 180
        ],
        'back_image' => [
          'path' => $fileImgIdentityBack['path'] ?? $genericPlaceholder,
          'width' => 300,
          'height' => 180
        ]
      ];


      $this->generateValidationCertificate($certicateData, $documentName, $certificateTemplatePath);

      $this->logger->info('Biometric validation certificated generated successfully.');

      $converted = $this->consolideFile($documentName);
      file_put_contents($pdfPath, $converted);
      $this->logger->info('Consolidate certificate done.');

      $this->putObject($pdfPath, $pdfName); // ? Upload certificate

      $this->logger->info('Generating zip file with biometric validation media and certificates.');
      $zipName = 'all_files_' . $signature['signature_code'] . '.zip';
      $signerPath = $document['uuid'] . '/' . $signature['signature_code']; // ? Old zip path

      $zip = new ZipArchive();
      $zip->open("$fileToUploadSigner/$zipName", ZipArchive::CREATE);
      $zip->addFile($pdfPath, $pdfName);
      $zip->addFile("$fileToUploadSigner/signedtsr.tsr", 'signedtsr.tsr');
      $zip->addFile("$fileToUploadSigner/signedtsq.tsq", 'signedtsq.tsq');
      if ($hasVideo) {
        $zip->addFile($fileToUploadSigner . '/' . $fileVideo['file'], $fileVideo['file']);
      }
      $zip->close();
      $this->logger->info('Zip file generated successfully');

      $zipRemoteUrl = $this->putObject("$fileToUploadSigner/$zipName", $zipName); // ? Put the zip in AWS bucket.

      // Guardar el video
      if ($hasVideo) {
        $videoFile = new FileItem();
        $videoFile->code = uniqid('file');
        $videoFile->document_id = $document['id'];
        $videoFile->signer_id = $signature['id'];
        $videoFile->name = $fileVideo['file'];
        $videoFile->url = $signerPath . '/' . $fileVideo['file']; // ? Old video url.
        $videoFile->remote_url = $fileVideo['remote_url'];
        $this->repository->save($videoFile);
      }

      /// Guardar el zip con los archivos y certificados
      $zipFile = new FileItem();
      $zipFile->code = uniqid();
      $zipFile->document_id = $document['id'];
      $zipFile->signer_id = $signature['id'];
      $zipFile->name = $zipName;
      $zipFile->url = "$signerPath/$zipName"; // ? Old zip url
      $zipFile->remote_url = $zipRemoteUrl;
      $this->repository->save($zipFile);

      $biometryData = [
        'current_step' => BiometryCurrentStep::Signature->value,
        'has_photo_identity_uploaded' => true,
        'has_biometric_identity_uploaded' => true,
        'has_video_identity_uploaded' => (int)$hasVideo,
        'session_id' => $data['files']['biometry']['session_id'],
        'scan_id' => $data['files']['biometry']['scan_id'],
        'is_done' => $data['files']['biometry']['is_done'],
        'completed_at' => date('Y-m-d H:i:s'),
      ];

      $conditions = [
        'document_id' => $data['document_id'],
        'signer_id' => $data['signer_id'],
      ];

      $this->biometryRepository->updateByConditions($conditions, $biometryData);
      $this->repository->pdo->commit();
      $this->logger->info(sprintf('Biometric validation for signer %s completed!', $signature['email']));
    } catch (\Exception $e) {
      $this->repository->pdo->rollBack();
      new DomainException($e->getMessage() . "\n");
    }
  }

  public function saveFile64(string $base64, string $outputDirectory, string $outputFileName, $field = ''): array
  {
    $decodeFile = base64_decode($base64);
    $f = finfo_open();

    $extension = explode('/', finfo_buffer($f, $decodeFile, FILEINFO_MIME_TYPE))[1];
    $formatAdmited = ['mp4', 'mov', 'webm', 'x-matroska', 'mkv', 'avi', 'wmv', 'jpg', 'png', 'jpeg'];
    $imgFormat = ['jpg', 'png', 'jpeg'];

    if (!in_array($extension, $formatAdmited)) {
      $field ??= $outputFileName;
      throw new DomainException('Wrong format in ' . $field . ', your extension is ' . $extension . ' only supported file formats: ' . implode(', ', $formatAdmited) . '. Your base64 must be sent without metadata.');
    }

    $fileName = "$outputFileName.$extension";
    $file = "$outputDirectory/$fileName";
    file_put_contents($file, $decodeFile);

    if (in_array($extension, $imgFormat)) {
      $this->compressImage($file, $file, 100);
    }

    $url = $this->putObject($file, $fileName);

    return [
      'path' => $file,
      'file' => $fileName,
      'remote_url' => $url
    ];
  }

  public function compressImage($source, $destination, $quality)
  {
    // Obtenemos la información de la imagen
    $imgInfo = getimagesize($source);
    $mime = $imgInfo['mime'];
    // Creamos una imagen
    switch ($mime) {
      case 'image/jpeg':
        $image = imagecreatefromjpeg($source);
        break;
      case 'image/png':
        $image = imagecreatefrompng($source);
        break;
      case 'image/gif':
        $image = imagecreatefromgif($source);
        break;
      default:
        $image = imagecreatefromjpeg($source);
    }
    // Guardamos la imagen
    imagejpeg($image, $destination, $quality);
    // Devolvemos la imagen comprimida
    return $destination;
  }

  public function downloadDocumentFile(array $data): string
  {
    $file = $this->repository->getByDocumentId($data['document_id']);
    return $this->getObjectB64($file['name']);
  }

  public function downloadByFileName(string $fileName): string
  {
    return $this->getObjectB64($fileName);
  }

  public function downloadValidationFiles(array $data): array
  {
    $document = $this->documentRepository->getById((int)$data['document_id']);
    $signature = $this->signatureRepository->getById((int)$data['signer_id']);

    $files = $this->repository->getByDocumentAndSignerId($document['id'], $signature['id']);

    $fileMeta = [
      'uuid' => $document['uuid'],
      'signature_code' => $signature['signature_code']
    ];

    $media = $this->getFiles($files, $fileMeta);

    return $media;
  }

  public function getFiles(array $files, array $meta): array
  {
    $filesList = [];
    foreach ($files as $file) {
      if (preg_match("/Front.jpeg$/", $file['name']) == 1) {
        array_push($filesList, ['identity_front' => $this->getObjectB64($file['name'])]);
      } elseif (preg_match("/Back.jpeg$/", $file['name']) == 1) {
        array_push($filesList, ['identity_back' => $this->getObjectB64($file['name'])]);
      } elseif (preg_match("/.mp4$/", $file['name']) == 1) {
        array_push($filesList, ['video' => $this->getObjectB64($file['name'])]);
      }
    }
    return $filesList;
  }


  public function generateValidationCertificate(array $data, string $documentName, string $templatePath): void
  {
    $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $templateProcessor->setImageValue($key, $value);
      } else {
        $templateProcessor->setValue($key, $value);
      }
    }
    $templateProcessor->saveAs($documentName);
  }

  public function generateTSA(string $path, string $output): void
  {
    hash_file('sha512', $path);
    exec('openssl ts -query -data ' . $path . ' -no_nonce -sha512 -cert -out ' . $output . '/signedtsq.tsq');
    exec('curl -H "Content-Type: application/timestamp-query" --data-binary "@' . $output . '/signedtsq.tsq" https://freetsa.org/tsr > ' . $output . '/signedtsr.tsr');
    exec('openssl ts -reply -in ' . $output . '/signedtsr.tsr -text -out ' . $output . '/response.txt');
  }

  public function consolideFile(string $documentName): string
  {
    $this->logger->info('Consolide certificate in a single pdf file.');
    $clientGuzzle = new Client();
    $headersGuzzle = [
      'Uuid' => $_SERVER['DOC_CONSOLIDADOR_UUID'],
    ];

    $requestGuzzle = new Request('POST', $_SERVER['URL_CONSOLIDADOR'], $headersGuzzle);
    $resGuzzle = $clientGuzzle->sendAsync($requestGuzzle, [
      'multipart' => [
        [
          'name' => 'files',
          'contents' => Utils::tryFopen($documentName, 'r'),
          'headers'  => [
            'Content-Type' => '<Content-type header>'
          ]
        ],
      ]
    ])->wait();
    return $resGuzzle->getBody();
  }
}
