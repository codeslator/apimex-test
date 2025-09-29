<?php

namespace App\Domain\File\Service;

use App\Domain\File\Repository\FileRepository;
use App\Domain\File\Data\FileItem;
use App\Domain\File\Utilities\FileValidator;
use App\Traits\AmazonS3Trait;

final class FileService
{
  use AmazonS3Trait;
  private FileRepository $repository;
  private FileValidator $validator;
  private string $documentPath = __DIR__ . '/../../../Documents/';

  public function __construct(FileRepository $repository, FileValidator $validator)
  {
    $this->repository = $repository;
    $this->validator = $validator;
  }

  public function getAll(): array
  {
    return $this->repository->getAll();
  }

  public function create(array $data): void
  {
    unset($data['page_sign']);
    unset($data['owner_id']);
    unset($data['signer_count']);
    unset($data['owner_type']);
    $this->validator->validateFile($data);

    $documentUuid = $data['uuid'];
    $filePath = "$this->documentPath/$documentUuid";

    if (!file_exists($filePath)) {
      mkdir($filePath, 0700); // Create a new directory with only read permissions
    }

    $decodedFile = base64_decode($data['file']); // Decode file to a base64
    $f = finfo_open();
    $extension = explode('/', finfo_buffer($f, $decodedFile, FILEINFO_MIME_TYPE))[1]; // Get the file extension

    if ($extension != 'pdf') {
      new \DomainException('Wrong format, only supported file pdf format');
    }

    $fileName = "$documentUuid.$extension";
    $file = "$filePath/$fileName";
    file_put_contents($file, (string)$decodedFile);

    $remote_url = $this->putObject($file, $fileName); // ? Put initial document in AWS bucket

    $file = new FileItem();
    $file->code = uniqid();
    $file->name = $fileName;
    $file->url = "$documentUuid/$fileName"; // ? Old document url
    $file->remote_url = $remote_url;
    $file->document_id = $data['document_id'];
    $file->signer_id = null;
    $this->repository->save($file);
  }

  public function getById(int $id): FileItem
  {
    return $this->transform($this->repository->getById($id));
  }

  public function getByDocumentId(int $documentId): FileItem
  {
    return $this->transform($this->repository->getByDocumentId($documentId)[0]);
  }

  public function updateByFileName(string $filename, array $data): void
  {
    $this->repository->updateByFileName($filename, $data);
  }

  public function delete(int $id): void
  {
    $this->repository->delete($id);
  }

  public function deleteByDocumentId(int $documentId): void
  {
    $this->repository->deleteByDocumentId($documentId);
  }

  public function removeDirectory(string $uuid): void
  {
    $dir = $this->documentPath . $uuid; // Concat path with uuid
    if (!$dh = @opendir($dir)) {
      return;
    }
    while (false !== ($current = readdir($dh))) {
      if ($current != '.' && $current != '..') {
        if (!@unlink($dir . '/' . $current)) {
          $this->removeDirectory($dir . '/' . $current);
        }
      }
    }
    closedir($dh);
    @rmdir($dir);
  }

  public function transform(array $row): FileItem
  {
    $file = new FileItem();
    $file->id = $row['id'];
    $file->code = $row['code'];
    $file->name = $row['name'];
    $file->document_id = $row['document_id'];
    return $file;
  }
}
