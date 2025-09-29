<?php

namespace App\Domain\Client\Service;

use App\Domain\Client\Data\ClientItem;
use App\Domain\ClientContact\Data\ClientContactItem;
use App\Domain\ClientContact\Repository\ClientContactRepository;
use App\Domain\User\Data\UserItem;
use App\Domain\Client\Repository\ClientRepository;
use App\Domain\ClientApiKey\Repository\ClientApiKeyRepository;
use App\Domain\User\Repository\UserRepository;
use App\Domain\Role\Repository\RoleRepository;
use App\Domain\Role\Data\RoleCode;
use App\Domain\Client\Utilities\ClientValidator;
use App\Domain\Client\Utilities\ClientUtils;
use App\Domain\ClientContact\Utilities\ClientContactUtils;
use App\Domain\Mail\Service\MailService;
use Ramsey\Uuid\Uuid;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

class ClientService
{
  private ClientRepository $repository;
  private ClientApiKeyRepository $clientApiKeyRepository;
  private UserRepository $userRepository;
  private ClientContactRepository $contactRepository;
  private RoleRepository $roleRepository;
  private MailService $mailService;
  private ClientValidator $validator;
  private ClientUtils $utils;
  private ClientContactUtils $contactUtils;
  private LoggerInterface $logger;

  public function __construct(
    ClientRepository $repository,
    ClientApiKeyRepository $clientApiKeyRepository,
    UserRepository $userRepository,
    RoleRepository $roleRepository,
    ClientContactRepository $contactRepository,
    MailService $mailService,
    ClientValidator $validator,
    ClientUtils $utils,
    ClientContactUtils $contactUtils,
    LoggerFactory $loggerFactory
  )
  {
    $this->repository = $repository;
    $this->clientApiKeyRepository = $clientApiKeyRepository;
    $this->userRepository = $userRepository;
    $this->roleRepository = $roleRepository;
    $this->contactRepository = $contactRepository;
    $this->mailService = $mailService;
    $this->validator = $validator;
    $this->utils = $utils;
    $this->contactUtils = $contactUtils;
    $this->logger = $loggerFactory
      ->addFileHandler('clients.log')
      ->createLogger();
  }

  public function create(array $data): void
  {
    try {
      $this->repository->pdo->beginTransaction();
      $this->validator->validate($data);
      // Create contact
      $contact = new ClientContactItem();
      $contact->uuid = Uuid::uuid4()->toString();
      $contact->rfc = $data['contact_rfc'];
      $contact->first_name = $this->contactUtils->capitalize($data['contact_first_name']);
      $contact->last_name = $this->contactUtils->capitalize($data['contact_last_name']);
      $contact->mother_last_name = $this->contactUtils->capitalize($data['contact_mother_last_name']);
      $contact->email = $data['contact_email'];
      $contact->phone = $data['contact_phone'] ?? null;
      $contact->curp = $data['contact_curp'] ?? null;
      $contactId = $this->contactRepository->save($contact);
      $this->logger->info("Creating contact for client ID [{$contactId}] with email [{$contact->email}].");

      // Create client
      $client = new ClientItem();
      $client->uuid = Uuid::uuid4()->toString();
      $client->name = $this->utils->capitalize($data['name']);
      $client->rfc = $data['rfc'];
      $client->description = $data['description'];
      $client->contact_id = (int) $contactId;
      $id = (int) $this->repository->save($client);
      $this->logger->info("Client [{$client->name}] created with ID [{$id}]. Contact email: {$contact->email}");
      
      $data = [
        'client_id' => $id,
        'rfc' => $client->rfc,
        'client_name' => $client->name,
        'password' => $this->utils->randomPassword(),
        'first_name' => $contact->first_name,
        'last_name' => $contact->last_name,
        'mother_last_name' => $contact->mother_last_name,
        'full_name' => trim("{$contact->first_name} {$contact->last_name} {$contact->mother_last_name}"),
        'email' => $contact->email,
        'phone' => $contact->phone,
      ];
      $this->createClientUser($data);
      $this->logger->info("Client user created for client ID [{$id}] with email [{$contact->email}].");
      $this->repository->pdo->commit();
    } catch (\Exception $e) {
      $this->repository->pdo->rollBack();
      throw new \Exception($e->getMessage());
    }
  }

  public function getAll(array $pagination): array
  {
    $data = $this->repository->getAll($pagination);
    if (count($data['data']) > 0) {
      $rows = [];
      foreach ($data['data'] as $apiKey) {
        $rows[] = $this->utils->transform($apiKey);
      }
      $data['data'] = $rows;
    }
    return $data;
  }

  public function createClientUser(array $data): void
  {
    $role = $this->roleRepository->getByCode(RoleCode::API_INTEGRATION->value);
    $user = new UserItem();
    $user->uuid = Uuid::uuid4()->toString();
    $user->client_id = $data['client_id'];
    $user->rfc = $data['rfc'];
    $user->first_name = $data['first_name'];
    $user->last_name = $data['last_name'];
    $user->mother_last_name = $data['mother_last_name'];
    $user->full_name = $data['full_name'];
    $user->email = $data['email'];
    $user->phone = $data['phone'] ?? null;
    $user->username = $data['email'];
    $user->password = hash('sha512', $data['password']);
    $user->role = (int) $role['id'];
    $user->pass_reset = false;
    $this->userRepository->save($user);

    $this->logger->info("Client User [{$user->full_name}] with email [{$user->email}] created for Client ID [{$user->client_id}].");

    $this->mailService->send(
      $data['email'],
      '¡Bienvenido a nuestra plataforma!',
      'register_client',
      [
        'clientName' => $data['client_name'],
        'contactEmail' => $data['email'],
        'password' => $data['password'],
        'domainUrl' => $_SERVER['DOMAINURL'],
        'supportEmail' => $_SERVER['SUPPORT_EMAIL']
      ]
    );
    $this->logger->info("Welcome email sent to Client User [{$user->full_name}] at [{$user->email}].");
  }

  public function getById(int $id): ClientItem
  {
    return $this->utils->transform($this->repository->getById($id));
  }

  public function deactivateById(int $id): void
  {
    $this->repository->disable($id);
    $this->clientApiKeyRepository->deactivateAllByClient($id);
    $this->logger->info("Client with ID [{$id}] has been disabled.");
  }
}
