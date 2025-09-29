<?php

namespace App\Domain\ClientContact\Data;

final class ClientContactItem
{
  public int $id;
  public string $uuid; 
  public string $rfc;
  public ?string $curp;
  public string $first_name;
  public string $last_name;
  public ?string $mother_last_name;
  public string $full_name;
  public string $email;
  public ?string $phone;
  public string $created_at;
  public ?string $updated_at;
}
