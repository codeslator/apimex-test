<?php

namespace App\Domain\Client\Utilities;

use App\Factory\ConstraintFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validation;

final class ClientValidator
{

  public function __construct() {}

  public function validate(array $data): void
  {
    $constraint = $this->createConstraints();

    $violations = Validation::createValidator()->validate($data, $constraint);
    if ($violations->count()) {
      throw new ValidationFailedException('Please check your input', $violations);
    }
  }

  private function createConstraints(): Constraint
  {
    $constraint = new ConstraintFactory();

    return $constraint->collection(
      [
        'name' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(5, 100),
          ]
        ),
        'rfc' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->rfc(),
            $constraint->length(5, 100)
          ]
        ),
        'contact_email' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->email(),
            $constraint->length(5, 100)
          ]
        ),
        'contact_first_name' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(2, 100),
          ]
        ),
        'contact_last_name' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(2, 100),
          ]
        ),
        'contact_mother_last_name' => $constraint->required(
          [
            $constraint->length(2, 100),
          ]
        ),
        'description' => $constraint->optional(
          [
            $constraint->length(5, 255),
          ]
        ),
        'contact_phone' => $constraint->optional(
          [
            $constraint->length(5, 13),
          ]
        ),
        'contact_rfc' => $constraint->required(
          [
            $constraint->length(5, 13),
          ]
        ),
        'contact_curp' => $constraint->optional(
          [
            $constraint->length(5, 20),
          ]
        ),
      ]
    );
  }
}
