<?php

namespace App\Domain\Authentication\Utilities;

use App\Factory\ConstraintFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validation;


final class AuthenticationValidator
{
  public function validate(array $data): void
  {
    $violations = Validation::createValidator()->validate($data, $this->loginConstraints());

    if ($violations->count()) {
      throw new ValidationFailedException('Please check your credentials', $violations);
    }
  }

  private function loginConstraints(): Constraint
  {
    $constraint = new ConstraintFactory();

    return $constraint->collection(
      [
        'email' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 255),
          ]
        ),
        'password' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 30),
          ]
        )
      ]
    );
  }
}
