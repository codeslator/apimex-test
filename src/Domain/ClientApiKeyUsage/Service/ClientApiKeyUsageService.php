<?php

namespace App\Domain\ClientApiKeyUsage\Service;

use Ramsey\Uuid\Uuid;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;
use App\Domain\ClientApiKeyUsage\Repository\ClientApiKeyUsageRepository;
use App\Domain\ClientApiKeyUsage\Data\ClientApiKeyUsageData;

final class ClientApiKeyUsageService {
 
  private ClientApiKeyUsageRepository $repository;
  private LoggerInterface $logger;

  public function __construct(
    ClientApiKeyUsageRepository $repository,
    LoggerFactory $loggerFactory
  )
  {
    $this->repository = $repository;
    $this->logger = $loggerFactory
      ->addFileHandler('apikey_usage.log')
      ->createLogger();
  }

  public function logUsage(int $clientApiKeyId, int $maxRequests, int $windowSeconds): void
  {
    $now = new \DateTime();
    $timestamp = $now->getTimestamp();
    $windowStartTimestamp = $timestamp - ($timestamp % $windowSeconds);
    $windowStart = (new \DateTime())->setTimestamp($windowStartTimestamp);

    // Buscar registro de uso en la ventana actual
    $existingUsage = $this->repository->findByApiKeyAndWindow([
        'client_api_key_id' => $clientApiKeyId,
        'window_start' => $windowStart->format('Y-m-d H:i:s')
    ]);

    if ($existingUsage) {
        // Incrementar contador y actualizar fecha de última consulta
        $newCount = $existingUsage['request_count'] + 1;
        $this->repository->updateUsage($existingUsage['id'], [
            'request_count' => $newCount,
            'last_request_at' => $now->format('Y-m-d H:i:s')
        ]);
        $this->logger->info("Incremented usage for API Key ID {$clientApiKeyId}. New request count: {$newCount}");

        // Verificar límite
        if ($newCount > $maxRequests) {
            $this->logger->warning("Rate limit exceeded for API Key ID {$clientApiKeyId}");
            throw new \DomainException('Rate limit exceeded. Try again later.');
        }
    } else {
        // Crear nuevo registro de uso
        $usage = new ClientApiKeyUsageData();
        $usage->client_api_key_id = $clientApiKeyId;
        $usage->request_count = 1;
        $usage->first_request_at = $now->format('Y-m-d H:i:s');
        $usage->last_request_at = $now->format('Y-m-d H:i:s');
        $usage->window_start = $windowStart->format('Y-m-d H:i:s');

        $this->repository->save($usage);
        $this->logger->info("Created new usage record for API Key ID {$clientApiKeyId}");
    }
  }

}