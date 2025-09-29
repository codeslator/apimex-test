<?php

namespace App\Domain\SignatureCredit\Service;

use App\Domain\SignatureCredit\Repository\SignatureCreditRepository;
use App\Domain\User\Repository\UserRepository;
use App\Domain\SignatureInventory\Repository\SignatureInventoryRepository;
use App\Domain\SignatureCredit\Data\SignatureCreditItem;
use App\Domain\SignatureInventory\Data\SignatureInventorySource;
use App\Domain\SignatureCredit\Utilities\SignatureCreditValidator;

final class SignatureCreditService
{

  private SignatureCreditRepository $repository;
  private UserRepository $userRepository;
  private SignatureInventoryRepository $inventoryRepository;
  private SignatureCreditValidator $validator;

  public function __construct(
    SignatureCreditRepository $repository,
    UserRepository $userRepository,
    SignatureInventoryRepository $inventoryRepository,
    SignatureCreditValidator $validator
  ) {
    $this->repository = $repository;
    $this->userRepository = $userRepository;
    $this->inventoryRepository = $inventoryRepository;
    $this->validator = $validator;
  }

  public function getAll(): array
  {
    return $this->repository->getAll();
  }

  public function create(array $data): void
  {
    $this->validator->validateSignatureCredit($data);
    $hasCredits = false;
    $user = $this->userRepository->getById((int)$data['user_id']);
    if (!empty($user['signature_credit_id'])) {
      $credit = $this->repository->getById((int)$user['signature_credit_id']);
      $hasCredits = true;
      if ((int)$credit['remaining_quantity'] <= 0) {
        $hasCredits = false;
      }
    }

    // Decrement from global inventory
    $inventory = $this->inventoryRepository->getBySource(SignatureInventorySource::PSC_WORLD->value);
    $inventoryRemainingQuantity = $inventory['quantity'] - (int)$data['quantity'];
    $inventoryData = [
      'quantity' => $inventoryRemainingQuantity
    ];

    $this->inventoryRepository->updateById((int)$inventory['id'], $inventoryData); // Update signatur global stock

    if ($hasCredits) { // If has credits, increment user credit
      $remainingQuantity = $credit['remaining_quantity'] + (int)$data['quantity'];
      $creditData = [
        'remaining_quantity' => $remainingQuantity,
      ];
      $this->repository->updateById((int)$credit['id'], $creditData);
    } else {
      // Then create the signature credit record
      $credit = new SignatureCreditItem();
      $credit->remaining_quantity = (int)$data['quantity'];
      $id = $this->repository->save($credit);

      // And update user record with signature credit id
      $userData = [
        'signature_credit_id' => (int)$id
      ];

      $this->userRepository->updateById((int)$data['user_id'], $userData);
    }
  }

  public function getById(int $id): ?SignatureCreditItem
  {
    return $this->transform($this->repository->getById($id));
  }

  public function getByUserId(int $userId): ?SignatureCreditItem
  {
    return $this->transform($this->repository->getByUserId($userId));
  }


  public function transform(mixed $row): ?SignatureCreditItem
  {
    if (is_array($row)) {
      $credit = new SignatureCreditItem();
      $credit->id = $row['id'];
      $credit->consumed_quantity = $row['consumed_quantity'];
      $credit->remaining_quantity = $row['remaining_quantity'];
      $credit->from_date = $row['from_date'];
      $credit->to_date = $row['to_date'];
      $credit->created_at = $row['created_at'];
      return $credit;
    }
    return null;
  }
}
