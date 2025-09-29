<?php

namespace App\Domain\Client\Utilities;

use App\Domain\ClientApiKey\Repository\ClientApiKeyRepository;
use App\Domain\ClientContact\Utilities\ClientContactUtils;
use App\Domain\User\Service\UserService;
use App\Domain\Client\Data\ClientItem;
use App\Domain\ClientApiKey\Utilities\ClientApiKeyUtils;
use App\Domain\ClientContact\Repository\ClientContactRepository;

final class ClientUtils
{

  private UserService $userService;
  private ClientApiKeyRepository $clientApiKeyRepository;
  private ClientContactRepository $contactRepository;
  private ClientContactUtils $contactUtils;
  private ClientApiKeyUtils $apiKeyUtils;

  public function __construct(
    UserService $userService,
    ClientApiKeyRepository $clientApiKeyRepository,
    ClientContactRepository $contactRepository,
    ClientContactUtils $contactUtils,
    ClientApiKeyUtils $apiKeyUtils
  ) {
    $this->userService = $userService;
    $this->clientApiKeyRepository = $clientApiKeyRepository;
    $this->contactRepository = $contactRepository;
    $this->contactUtils = $contactUtils;
    $this->apiKeyUtils = $apiKeyUtils;
  }

  public function capitalize(mixed $value): ?string
  {
    if (!isset($value) || !is_string($value) || trim($value) === '' || $value === null) {
      return null;
    }
    return ucwords(strtolower($value));
  }

  public function randomPassword(int $length = 12): string
  {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }

  public function transform(array $row): ClientItem
  {
    $apiKeys = $this->clientApiKeyRepository->getAllByClientId((int) $row['id']);
    $client = new ClientItem();
    $client->id = (int) $row['id'];
    $client->uuid = $row['uuid'];
    $client->name = $row['name'];
    $client->rfc = $row['rfc'];
    $client->description = $row['description'];
    $client->is_active = (bool) $row['is_active'];
    $client->webhook_url = $row['webhook_url'];
    $client->api_keys = $this->transformApiKeys($apiKeys);
    $client->user = $this->userService->getByClientId((int) $row['id']) ?? null;
    $client->contact = ($row['contact_id']) ? $this->contactUtils->transform($this->contactRepository->getById((int) $row['contact_id'])) : null;
    $client->created_at = $row['created_at'];
    $client->updated_at = $row['updated_at'];
    return $client;
  }

  public function transformApiKeys(array $rows): array
  {
    $apiKeys = [];
    foreach ($rows as $row) {
      $apiKeys[] = $this->apiKeyUtils->transform($row);
    }
    return $apiKeys;
  }
}
