<?php

namespace App\Domain\File\Utilities;

use App\Factory\ConstraintFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validation;

final class FileValidator
{
  public function validateVideo(array $data): void
  {
    $violations = Validation::createValidator()->validate($data, $this->videoConstraints());

    if ($violations->count()) {
      throw new ValidationFailedException('Please check your input', $violations);
    }
  }

  public function validateFile(array $data): void
  {
    $violations = Validation::createValidator()->validate($data, $this->fileConstraints());

    if ($violations->count()) {
      throw new ValidationFailedException('Please check your input', $violations);
    }
  }

  private function fileConstraints(): Constraint
  {
    $constraint = new ConstraintFactory();

    return $constraint->collection(
      [
        'file' => $constraint->required(
          [
            $constraint->notBlank()
          ]
        ),
        'document_id' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->number(),
          ]
        ),
        'uuid' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 255),
          ]
        ),
        'signers' => $constraint->required(
          [
            $constraint->notBlank(),
          ]
        ),
        'document_type_fee_id' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->number(),
          ]
        ),
      ]
    );
  }

  private function videoConstraints(): Constraint
  {
    $constraint = new ConstraintFactory();

    return $constraint->collection(
      [
        'files' => $constraint->required(
          [
            $constraint->notBlank()
          ]
        ),
        'document_id' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 255),
          ]
        ),
        'signer_id' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 255),
          ]
        ),
        "ip_address" => $constraint->required(
          [
            $constraint->notBlank(),
            // $constraint->length(null, 255),
          ]
        ),
      ]
    );
  }
}
