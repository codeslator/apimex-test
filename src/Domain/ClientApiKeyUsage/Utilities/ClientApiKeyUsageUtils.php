<?php

namespace App\Domain\ClientApiKeyUsage\Utilities;
use App\Domain\ClientApiKeyUsage\Data\ClientApiKeyUsageData;
use App\Domain\ClientApiKeyUsage\Repository\ClientApiKeyUsageRepository;

final class ClientApiKeyUsageUtils
{
  private ClientApiKeyUsageRepository $usageRepository;
  public function __construct(
    ClientApiKeyUsageRepository $usageRepository
  ) {
    $this->usageRepository = $usageRepository;
  }

  public function transform(array $row): ClientApiKeyUsageData
  {
    $usage = new ClientApiKeyUsageData();
    $usage->id = (int) $row['id'];
    $usage->client_api_key_id = (int) $row['client_api_key_id'];
    $usage->request_count = (int) $row['request_count'];
    $usage->first_request_at = $row['first_request_at'];
    $usage->last_request_at = $row['last_request_at'];
    $usage->window_start = $row['window_start'];
    return $usage;
  }

  public function lastUsage(int $apikeyId): ?object
  {
    $usage = $this->usageRepository->getUsageByApiKeyId($apikeyId);
    if (!$usage) {
      return null;
    }
    return (object)[
      'total_requests' => (int) $usage['total_requests'],
      'first_request_at' => $usage['first_request_at'],
      'last_request_at' => $usage['last_request_at'],
    ];
  }
}
