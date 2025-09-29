<?php

namespace App\Domain\User\Service;

use App\Domain\User\Repository\UserRepository;
use App\Domain\User\Utilities\UserValidator;
use App\Domain\User\Utilities\UserUtils;
use App\Domain\User\Data\UserItem;
use Ramsey\Uuid\Uuid;
use App\Domain\Mail\Service\MailService;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

final class UserService
{

  private UserRepository $repository;
  private UserValidator $validator;
  private UserUtils $utils;
  private MailService $mailService;

  private LoggerInterface $logger;

  public function __construct(
    UserRepository $repository,
    UserValidator $validator,
    UserUtils $utils,
    MailService $mailService,
    LoggerFactory $loggerFactory
  ) {
    $this->repository = $repository;
    $this->validator = $validator;
    $this->utils = $utils;
    $this->mailService = $mailService;
    $this->logger = $loggerFactory
      ->addFileHandler('users_creator.log')
      ->createLogger();
  }

  public function getAll(array $pagination): array
  {
    $data = $this->repository->getAll($pagination);
    if (count($data['data']) > 0) {
      $rows = [];
      foreach ($data['data'] as $users) {
        $rows[] = $this->transform($users);
      }
      $data['data'] = $rows;
    }
    return $data;
  }

  public function create(array $data): void
  {
    $this->validator->validateUser($data, null);
    $user = new UserItem();
    $user->rfc = strtoupper($data['rfc']);
    $user->uuid = Uuid::uuid4()->toString();
    $user->first_name = $this->utils->capitalize($data['first_name']);
    $user->last_name = $this->utils->capitalize($data['last_name']);
    $user->mother_last_name = $this->utils->capitalize($data['mother_last_name']);
    $user->username = strtolower($data['username']);
    $user->email = strtolower($data['email']);
    $user->phone = $data['phone'];
    $user->password = hash('sha512', $data['password']);
    $user->role = $data['role_id'];
    $user->pass_reset = false;
    $id = $this->repository->save($user);

    $this->mailService->send(
      $data['email'],
      '¡Registro Exitoso!',
      'registerUserNotify',
      [
        'fullName' => $data['first_name'] . ' ' . $data['last_name'],
        'domainUrl' => $_SERVER['DOMAINURL'],
        'supportEmail' => $_SERVER['SUPPORT_EMAIL']
      ]
    );

    // Logging
    $this->logger->info(sprintf('User created successfully: %s', $id));
  }

  public function update(int $id, array $data): void
  {
    $this->validator->validateUser($data, $id);
    $user = $this->getById($id);
    $user->rfc = $data['rfc'];
    $user->first_name = $this->utils->capitalize($data['first_name']);
    $user->last_name = $this->utils->capitalize($data['last_name']);
    $user->mother_last_name = $this->utils->capitalize($data['mother_last_name']);
    $user->username = strtolower($data['username']);
    $user->email = strtolower($data['email']);
    $user->phone = $data['phone'];
    $user->role = $data['role_id'];
    $this->repository->update($id, $user);
    $this->logger->info(sprintf('User updated successfully: %s', $id));
  }

  public function delete(int $id): void
  {
    $this->repository->delete($id);
    $this->logger->info(sprintf('User deleted successfully: %s', $id));
  }

  public function getById(int $id): UserItem
  {
    return $this->transform($this->repository->getById($id));
  }

  public function getByClientId(int $clientId): UserItem
  {
    return $this->transform($this->repository->getByClientId($clientId));
  }

  public function transform(array $row): UserItem
  {
    $user = new UserItem();
    $user->id = $row['id'];
    $user->uuid = $row['uuid'];
    $user->rfc = $row['rfc'];
    $user->first_name = $row['first_name'];
    $user->last_name = $row['last_name'];
    $user->mother_last_name = $row['mother_last_name'];
    $user->full_name = $row['full_name'] ?? "{$row['first_name']} {$row['last_name']} {$row['mother_last_name']}";
    $user->username = $row['username'];
    $user->email = $row['email'];
    $user->phone = $row['phone'];
    $user->role = $this->repository->getRoleForUser($row['role_id']);
    $user->signature_credit = (!empty($row['signature_credit_id'])) ? $this->repository->getSignatureCreditsForUser($row['signature_credit_id']) : null;
    $user->pass_reset = $row['pass_reset'];
    $user->client_id = $row['client_id'];
    $user->created_at = $row['created_at'];
    return $user;
  }

  public function checkUserExists(array $data): bool
  {
    return $this->repository->checkUserExists($data['email']);
  }

  public function changePassword(string $id, array $data): void
  {
    $password = hash('sha512', $data['password']);
    $passwordConfirm = hash('sha512', $data['password_confirm']);

    if ($password != $passwordConfirm) {
      throw new \DomainException("The password and password confirm don't match.");
    }

    unset($data['password_confirm']);
    $data['password'] = $password;
    $this->repository->changePassword((int)$id, $data);
  }

  public function getUserByTerm(array $pagination): array
  {
    $data = $this->repository->getUserByTerm($pagination);
    if (count($data['data']) > 0) {
      $rows = [];
      foreach ($data['data'] as $users) {
        $rows[] = $this->transform($users);
      }
      $data['data'] = $rows;
    }
    return $data;
  }
}
