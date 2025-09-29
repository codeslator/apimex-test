<?php

namespace App\Domain\ClientContact\Utilities;

use App\Factory\ConstraintFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validation;

final class ClientContactValidator
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
        'rfc' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->rfc(),
            $constraint->length(5, 100)
          ]
        ),
        'curp' => $constraint->optional(
          [
            $constraint->curp(),
            $constraint->length(10, 18)
          ]
        ),
        'email' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->email(),
            $constraint->length(5, 100)
          ]
        ),
        'first_name' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(2, 100),
          ]
        ),
        'last_name' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(2, 100),
          ]
        ),
        'mother_last_name' => $constraint->optional(
          [
            $constraint->length(2, 100),
          ]
        ),
        'phone' => $constraint->optional(
          [
            $constraint->length(5, 15),
          ]
        ),
      ]
    );
  }
}
