<?php

namespace App\Domain\SignaturePackagePurchase\Utilities;

use App\Factory\ConstraintFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validation;

final class SignaturePackagePurchaseValidator
{

  public function __construct() {}

  public function validatePurchase(array $data): void
  {
    $constraint = $this->createPurchaseConstraints();

    $violations = Validation::createValidator()->validate($data, $constraint);
    if ($violations->count()) {
      throw new ValidationFailedException('Please check your input', $violations);
    }
  }

  private function createPurchaseConstraints(): Constraint
  {
    $constraint = new ConstraintFactory();

    return $constraint->collection(
      [
        'package_id' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->number(),
          ]
        ),
        'user_id' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->number(),
          ]
        ),
        'quantity' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->number(),
          ]
        ),
      ]
    );
  }
}
