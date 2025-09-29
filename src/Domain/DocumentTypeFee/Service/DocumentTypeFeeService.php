<?php

namespace App\Domain\DocumentTypeFee\Service;

use App\Domain\DocumentTypeFee\Repository\DocumentTypeFeeRepository;
use App\Domain\DocumentTypeFee\Data\DocumentTypeFeeItem;

final class DocumentTypeFeeService
{

  private DocumentTypeFeeRepository $repository;

  public function __construct(DocumentTypeFeeRepository $repository)
  {
    $this->repository = $repository;
  }

  public function getAll(): array
  {
    return $this->repository->getAll();
  }

  public function create(array $data): void
  {
    $documentTypeFee = new DocumentTypeFeeItem();
    $documentTypeFee->modality = $data['modality'];
    $documentTypeFee->sign_count = $data['sign_count'];
    $documentTypeFee->amount = $data['amount'];
    $documentTypeFee->amount_iva = $data['amount_iva'];
    $documentTypeFee->iva = $data['iva'];
    $documentTypeFee->total = $data['total'];
    $documentTypeFee->is_active = true;
    $documentTypeFee->document_type_id = $data['document_type_id'];
    $this->repository->save($documentTypeFee);
  }

  public function getById(int $id): DocumentTypeFeeItem
  {
    return $this->transform($this->repository->getById($id));
  }

  public function transform(array $row): DocumentTypeFeeItem
  {
    $documentTypeFee = new DocumentTypeFeeItem();
    $documentTypeFee->id = $row['id'];
    $documentTypeFee->modality = $row['modality'];
    $documentTypeFee->sign_count = $row['sign_count'];
    $documentTypeFee->amount = $row['amount'];
    $documentTypeFee->amount_iva = $row['amount_iva'];
    $documentTypeFee->iva = $row['iva'];
    $documentTypeFee->total = $row['total'];
    $documentTypeFee->is_active = $row['is_active'];
    // $documentTypeFee->document_type_id = $row['document_type_id'];
    $documentTypeFee->document_type = $this->repository->getDocumentType($row['document_type_id']);
    return $documentTypeFee;
  }
}
