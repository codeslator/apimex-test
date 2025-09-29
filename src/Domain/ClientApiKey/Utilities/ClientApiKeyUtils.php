<?php

namespace App\Domain\ClientApiKey\Utilities;

use App\Domain\Client\Repository\ClientRepository;
use App\Domain\ClientApiKey\Data\ClientApiKeyItem;
use App\Domain\Client\Data\ClientItem;
use App\Domain\ClientApiKeyUsage\Utilities\ClientApiKeyUsageUtils;
use App\Domain\User\Service\UserService;

final class ClientApiKeyUtils
{
  private ClientApiKeyUsageUtils $usageUtils;
  private ClientRepository $clientRepository;
  private UserService $userService;

  public function __construct(
    ClientApiKeyUsageUtils $usageUtils,
    ClientRepository $clientRepository,
    UserService $userService
  ) {
    $this->usageUtils = $usageUtils;
    $this->clientRepository = $clientRepository;
    $this->userService = $userService;
  }

  public function transform(array $row, $isDeep = false): ClientApiKeyItem
  {
    $apikey = new ClientApiKeyItem();
    $apikey->id = (int) $row['id'];
    if ($isDeep) {
      $apikey->client = $this->transformClient($this->clientRepository->getById($row['client_id']));
    }
    else {
      $apikey->client_id = (int) $row['client_id'];
    }
    $apikey->api_key = $row['api_key'];
    $apikey->name = $row['name'];
    $apikey->description = $row['description'];
    $apikey->environment = $row['environment'];
    $apikey->rate_limit = $row['rate_limit'];
    $apikey->rate_limit_window = $row['rate_limit_window'];
    $apikey->usage = $this->usageUtils->lastUsage((int)$row['id']);
    $apikey->created_at = $row['created_at'];
    $apikey->rotated_at = $row['rotated_at'];
    $apikey->status = $row['status'];
    $apikey->last_used_at = $row['last_used_at'];
    $apikey->expires_at = $row['expires_at'];
    return $apikey;
  }

  public function transformClient(array $row): ClientItem
  {
    $client = new ClientItem();
    $client->id = (int) $row['id'];
    $client->uuid = $row['uuid'];
    $client->name = $row['name'];
    $client->rfc = $row['rfc'];
    $client->description = $row['description'];
    $client->is_active = (bool) $row['is_active'];
    $client->webhook_url = $row['webhook_url'];
    $client->user = $this->userService->getByClientId($client->id);
    $client->created_at = $row['created_at'];
    $client->updated_at = $row['updated_at'];
    unset($client->api_keys);
    return $client;
  }
}