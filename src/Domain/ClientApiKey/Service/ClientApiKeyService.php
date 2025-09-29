<?php

namespace App\Domain\ClientApiKey\Service;

use App\Domain\ClientApiKey\Repository\ClientApiKeyRepository;
use App\Domain\Client\Repository\ClientRepository;
use App\Domain\ClientApiKey\Data\ClientApiKeyItem;
use App\Domain\ClientApiKey\Utilities\ClientApiKeyUtils;
use App\Domain\Client\Utilities\ClientUtils;
use App\Domain\ClientApiKey\Data\ClientApiKeyEnvironment;
use App\Domain\ClientContact\Repository\ClientContactRepository;
use App\Domain\Mail\Service\MailService;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

class ClientApiKeyService
{
  private ClientApiKeyRepository $repository;
  private ClientRepository $clientRepository;
  private ClientContactRepository $contactRepository;
  private ClientApiKeyUtils $utils;
  private ClientUtils $clientUtils;
  private MailService $mailService;
  private LoggerInterface $logger;


  public function __construct(
    ClientApiKeyRepository $repository,
    ClientRepository $clientRepository,
    ClientContactRepository $contactRepository,
    ClientApiKeyUtils $utils,
    ClientUtils $clientUtils,
    MailService $mailService,
    LoggerFactory $loggerFactory
  ) {
    $this->repository = $repository;
    $this->clientRepository = $clientRepository;
    $this->contactRepository = $contactRepository;
    $this->utils = $utils;
    $this->clientUtils = $clientUtils;
    $this->mailService = $mailService;
    $this->logger = $loggerFactory
      ->addFileHandler('apikeys.log')
      ->createLogger();
  }

  public function getAll(array $pagination): array
  {
    $data = $this->repository->getAll($pagination);
    if (count($data['data']) > 0) {
      $rows = [];
      foreach ($data['data'] as $apiKey) {
        $rows[] = $this->utils->transform($apiKey, true);
      }
      $data['data'] = $rows;
    }
    return $data;
  }

  public function getById(int $id): ?ClientApiKeyItem
  {
    return $this->utils->transform($this->repository->getById($id), true);
  }

  public function getByClientId(int $clientId): array
  {
    $data = $this->repository->getAllByClientId($clientId);
    if (count($data) > 0) {
      $rows = [];
      foreach ($data as $apiKey) {
        $rows[] = $this->utils->transform($apiKey);
      }
      return $rows;
    }
    return $data;
  }


  public function validateKey(string $key): object
  {
    $apikey = $this->repository->findActiveByKey($key);

    if (!$apikey) {
      throw new \DomainException('API Key not found or inactive.');
    }

    // Verificar expiración
    if (!empty($apikey['expires_at']) && strtotime($apikey['expires_at']) < time()) {
      throw new \DomainException('API Key has expired.');
    }

    // Registrar último uso para auditoría
    $this->repository->updateLastUsedAt($apikey['id'], date('Y-m-d H:i:s'));

    // Obtener datos del cliente asociado
    $client = $this->clientRepository->getById($apikey['client_id']);
    if (!$client) {
      throw new \DomainException('Client not found for this API Key.');
    }

    if (!(bool)$client['is_active']) {
      throw new \DomainException('Client associated with this API Key is inactive.');
    }
    // Retornar objeto con cliente + API Key
    $clientData = $this->clientUtils->transform($client);
    $apikeyData = $this->utils->transform($apikey);
    return (object) [
      'api_key' => $apikeyData,
      'client'  => $clientData,
      'usage'   => $apikeyData->usage,
      'role'    => $clientData->user->role // Rol fijo para integraciones
    ];
  }

  public function generateKey(array $data): string
  {
    $newKey = $this->generateApiKey('fvmx', $data['environment']);
    $this->repository->createKey($data['client_id'], $newKey);
    return $newKey;
  }

  public function deactivateAllByClientId(int $clientId): void
  {
    $this->repository->deactivateAllByClient($clientId);
    $this->logger->info("All API Keys deactivated for Client ID [{$clientId}].");
  }

  public function create(array $data): string
  {
    $apiKey = $this->generateApiKey('fvmx', $data['environment']);
    $client = $this->clientRepository->getById($data['client_id']);

    if (!$client) {
      throw new \DomainException('Client not found for the specified client ID.');
    }

    $contact = $this->contactRepository->getById($client['contact_id']);

    if (filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
      $apiKeyItem = new ClientApiKeyItem();
      $apiKeyItem->client_id = $data['client_id'];
      $apiKeyItem->api_key = $apiKey;
      $apiKeyItem->name = $data['name'];
      $apiKeyItem->description = $data['description'];
      $apiKeyItem->environment = $data['environment'];
      $apiKeyItem->rate_limit = isset($data['rate_limit']) ? $data['rate_limit'] : 1000;
      $apiKeyItem->rate_limit_window = isset($data['rate_limit_window']) ? $data['rate_limit_window'] : 3600;
      $this->repository->save($apiKeyItem);
      $this->logger->info("API Key [{$apiKey}] created for Client ID [{$data['client_id']}] in environment [{$data['environment']}].");

      $this->logger->info("Sending API Key creation email to {$contact['email']} for Client ID [{$data['client_id']}].");
      $this->mailService->send(
        $contact['email'],
        'Su nueva clave API ha sido creada',
        'new_client_apikey',
        [
          'clientName' => $client['name'],
          'apiKey' => $apiKey,
          'environment' => $data['environment'],
          'domainUrl' => $_SERVER['DOMAINURL'],
          'supportEmail' => $_SERVER['SUPPORT_EMAIL']
        ]
      );
    } else {
      $this->logger->warning("No valid email found for Client ID [{$data['client_id']}]. Skipping API Key creation email.");
    }
    return $apiKey;
  }

  public function rotate(array $data): string
  {
    $apikey = $this->repository->getByClientIdAndEnvironment($data['client_id'], $data['environment']);
    if (!$apikey) {
      throw new \DomainException('API Key not found for the specified client and environment.');
    }
    $this->repository->deactivateKey($apikey);
    $this->logger->info("API Key [{$apikey['api_key']}] deactivated for Client ID [{$data['client_id']}] in environment [{$data['environment']}].");

    $data['name'] = $apikey['name'];
    $data['description'] = $apikey['description'];

    $apikey = $this->create($data);
    $this->logger->info("New API Key [{$apikey}] created for Client ID [{$data['client_id']}] in environment [{$data['environment']}].");
    return $apikey;
  }

  public function generateApiKey(string $prefix, string $environment): string
  {
    $environmentMap = [
      ClientApiKeyEnvironment::PRODUCTION->value => 'live',
      ClientApiKeyEnvironment::STAGING->value => 'test',
      ClientApiKeyEnvironment::DEVELOPMENT->value => 'dev'
    ];
    return $prefix . '_' . $environmentMap[$environment] . '_' . bin2hex(random_bytes(28));
  }

  public function deactivateKeyById(int $id): void
  {
    $apikey = $this->repository->getById($id);
    if (!$apikey) {
      throw new \DomainException('API Key not found.');
    }
    $this->repository->deactivateKey($apikey);
    $this->logger->info("API Key [{$apikey['api_key']}] deactivated for Client ID [{$apikey['client_id']}] in environment [{$apikey['environment']}].");
  }
}
