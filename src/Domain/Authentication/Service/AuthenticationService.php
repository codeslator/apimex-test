<?php

namespace App\Domain\Authentication\Service;

use App\Domain\User\Data\UserItem;
use App\Domain\Role\Repository\RoleRepository;
use App\Domain\User\Repository\UserRepository;
use App\Domain\User\Utilities\UserValidator;
use App\Domain\User\Utilities\UserUtils;
use App\Domain\Authentication\Repository\AuthenticationRepository;
use App\Domain\Authentication\Repository\JwtRepository;
use App\Domain\Authentication\Service\JwtService;
use App\Domain\Authentication\Utilities\AuthenticationValidator;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use App\Domain\Mail\Service\MailService;
use Firebase\JWT\JWT;


final class AuthenticationService
{

  private AuthenticationRepository $repository;
  private JwtRepository $jwtRepository;
  private RoleRepository $roleRepository;
  private AuthenticationValidator $validator;
  private UserRepository $userRepository;
  private UserValidator $userValidator;
  private UserUtils $userUtils;
  private MailService $mailService;
  private JwtService $jwtService;

  private LoggerInterface $logger;

  public function __construct(
    AuthenticationRepository $repository,
    JwtRepository $jwtRepository,
    AuthenticationValidator $validator,
    LoggerFactory $loggerFactory,
    RoleRepository $roleRepository,
    UserRepository $userRepository,
    UserValidator $userValidator,
    UserUtils $userUtils,
    MailService $mailService,
    JwtService $jwtService
  ) {
    $this->repository = $repository;
    $this->jwtRepository = $jwtRepository;
    $this->validator = $validator;
    $this->roleRepository = $roleRepository;
    $this->userRepository = $userRepository;
    $this->userValidator = $userValidator;
    $this->userUtils = $userUtils;
    $this->mailService = $mailService;
    $this->jwtService = $jwtService;
    $this->logger = $loggerFactory
      ->addFileHandler('users_login.log')
      ->createLogger();
  }

  public function loginByCredentials(array $data): array
  {
    try {
      $this->validator->validate($data);
      $loggedUser = $this->jwtRepository->loginByCredentials($data);

      if (!$loggedUser) {
        $this->logger->error(sprintf('Invalid credentials for user [%s].', $data['email']));
        throw new \Exception('Invalid credentials or user not found.');
      }

      $user = $this->transformUser($loggedUser);
      $permissions = $this->roleRepository->getPermissionsForRole($user->role->id);

      $token = $this->jwtService->generateToken(['user' => $user]);

      $this->logger->info(sprintf('User [%s] logged in successfully', $user->email));

      return [
        'token' => $token,
        'user' => $user,
        'permissions' => $this->mapPermissions($permissions),
        'role' => $user->role->code,
        'password_reset' => (bool) $user->pass_reset
      ];
    } catch (\Exception $e) {
      $this->logger->error(sprintf('Login failed for user [%s]', $data['email']));
      throw new \DomainException($e->getMessage());
    }
  }

  public function register(array $data): void
  {
    $this->userValidator->validateUser($data, null);
    $user = new UserItem();
    $user->rfc = strtoupper($data['rfc']);
    $user->uuid = Uuid::uuid4()->toString();
    $user->first_name = $this->userUtils->capitalize($data['first_name']);
    $user->last_name = $this->userUtils->capitalize($data['last_name']);
    $user->mother_last_name = $this->userUtils->capitalize($data['mother_last_name']);
    $user->username = strtolower($data['username']);
    $user->email = strtolower($data['email']);
    $user->phone = $data['phone'];
    $user->password = hash('sha512', $data['password']);
    $user->role = $data['role_id'];
    $user->pass_reset = false;
    $id = $this->userRepository->save($user);

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
    $this->logger->info(sprintf('User registered successfully: %s', $id));
  }

  private function mapPermissions(array $permissions): array
  {
    $permissionCodes = [];
    foreach ($permissions as $permission) {
      $permissionCodes[] = $permission->code;
    }
    return $permissionCodes;
  }

  public function resetPassword(string $email): mixed
  {
    if (!$this->userRepository->checkUserExists($email)) {
      return false;
    }

    $i = ((int) date("sis", time())) * rand(10, 100);
    $newPassword = (string)ceil((3 * $i - 1.5 * $i) / 2);
    $this->repository->resetPassword($email, hash('sha512', $newPassword));

    $this->mailService->send(
      $email,
      'Usted ha solicitado cambio de contraseña',
      'resetPassword',
      ['password' => $newPassword]
    );

    return true;
  }

  public function transformUser(array $row): UserItem
  {
    $user = new UserItem();
    $user->id = (int)$row['id'];
    $user->uuid = $row['uuid'];
    $user->rfc = $row['rfc'];
    $user->first_name = $row['first_name'];
    $user->last_name = $row['last_name'];
    $user->mother_last_name = $row['mother_last_name'];
    $user->username = $row['username'];
    $user->email = $row['email'];
    $user->phone = $row['phone'];
    $user->role = $this->userRepository->getRoleForUser($row['role_id']);
    $user->signature_credit = (!empty($row['signature_credit_id'])) ? $this->userRepository->getSignatureCreditsForUser($row['signature_credit_id']) : null;
    $user->pass_reset = (bool) $row['pass_reset'];
    $user->created_at = $row['created_at'];
    return $user;
  }
}
