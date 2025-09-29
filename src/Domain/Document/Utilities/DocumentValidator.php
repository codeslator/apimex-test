<?php

namespace App\Domain\Document\Utilities;

use App\Domain\Document\Repository\DocumentRepository;
use App\Factory\ConstraintFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validation;

final class DocumentValidator
{
  private DocumentRepository $repository;

  public function __construct(DocumentRepository $repository)
  {
    $this->repository = $repository;
  }

  public function validateDocument(array $data): void
  {
    $violations = Validation::createValidator()->validate($data, $this->createConstraints());

    if ($violations->count()) {
      throw new ValidationFailedException('Please check your input', $violations);
    }
  }

  private function createConstraints(): Constraint
  {
    $constraint = new ConstraintFactory();

    return $constraint->collection(
      [
        'file' => $constraint->required(
          [
            $constraint->notBlank(),
          ]
        ),
        'signers' => $constraint->required(
          [
            $constraint->notBlank(),
          ]
        ),
        'page_sign' => $constraint->required(
          [
            $constraint->notBlank(),
          ]
        ),
      ]
    );
  }

  public function validateSigners(array $data): void
  {
    foreach ($data['signers'] as $signer) {
      $violations = Validation::createValidator()->validate($signer, $this->createSignatureConstraints());
      if ($violations->count()) {
        throw new ValidationFailedException('Please check your input signers', $violations);
      }
    }
  }

  private function createSignatureConstraints(): Constraint
  {
    $constraint = new ConstraintFactory();

    return $constraint->collection(
      [
        'rfc' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 20),
            $constraint->rfc(),
          ]
        ),
        'first_name' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 255),
          ]
        ),
        'last_name' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 255),
          ]
        ),
        'mother_last_name' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 255),
          ]
        ),
        'email' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 255),
          ]
        ),
        'role' => $constraint->required(
          [
            $constraint->notBlank(),
          ]
        ),
        'signer_type' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 255),
          ]
        ),
        'portion' => $constraint->required(
          [
            $constraint->notBlank(),
          ]
        ),
        'require_video' => $constraint->optional(
          [
            $constraint->bolean(),
          ]
        ),
      ]
    );
  }
}
