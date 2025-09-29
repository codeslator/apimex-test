<?php

namespace App\Domain\SignatureCredit\Utilities;

use App\Factory\ConstraintFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validation;

final class SignatureCreditValidator
{
  public function validateSignatureCredit(array $data): void
  {
    $violations = Validation::createValidator()->validate($data, $this->creditConstraints());

    if ($violations->count()) {
      throw new ValidationFailedException('Please check your input', $violations);
    }
  }

  private function creditConstraints(): Constraint
  {
    $constraint = new ConstraintFactory();

    return $constraint->collection(
      [
        'quantity' => $constraint->required(
          [
            $constraint->number(),
            $constraint->notBlank()
          ]
        ),
        'user_id' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->number(),
          ]
        ),
      ]
    );
  }

}
