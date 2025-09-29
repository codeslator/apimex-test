<?php

namespace App\Domain\Client\Data;

use App\Domain\ClientContact\Data\ClientContactItem;
use App\Domain\User\Data\UserItem;

final class ClientItem
{
  public int $id;
  public string $uuid; 
  public string $name;
  public string $rfc;
  public ?string $description;
  public ?string $webhook_url;
  public ?string $rate_limit;
  public bool $is_active;
  public array $api_keys = [];
  public ?UserItem $user = null;
  public ?ClientContactItem $contact = null;
  public int $contact_id;
  public string $created_at;
  public ?string $updated_at;
}
