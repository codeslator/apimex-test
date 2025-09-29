<?php

namespace App\Domain\User\Utilities;

use App\Domain\User\Data\UserItem;
use App\Domain\User\Repository\UserRepository;
use App\Factory\ConstraintFactory;
use DomainException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validation;

final class UserValidator
{
  private UserRepository $repository;

  public function __construct(UserRepository $repository)
  {
    $this->repository = $repository;
  }

  public function validateUser(array $data, ?int $id): void
  {
    $constraint = $this->createConstraints();
    if(!is_null($id)) {
      $constraint = $this->updateConstraints();
    }
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
            $constraint->email(),
          ]
        ),
        'username' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 255),
          ]
        ),
        'password' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 255),
          ]
        ),
        'phone' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 18),
          ]
        ),
        'role_id' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->number(),
          ]
        ),
      ]
    );
  }

  private function updateConstraints(): Constraint
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
            $constraint->email(),
          ]
        ),
        'username' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 255),
          ]
        ),
        'phone' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 18),
          ]
        ),
        'role_id' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->number(),
          ]
        ),
      ]
    );
  }
}
