<?php

namespace App\Domain\ClientApiKey\Data;

final class ClientApiKeyItem
{
  public int $id;
  public int $client_id; // FK
  public ?object $client;
  public string $api_key;
  public ?string $name;
  public ?string $description;
  public string $environment;
  public string $status;
  public ?int $rate_limit;
  public ?int $rate_limit_window;
  public ?object $usage = null;
  public ?string $expires_at;
  public ?string $last_used_at;
  public ?string $rotated_at;
  public string $created_at;
  public ?string $revoked_at;
}
