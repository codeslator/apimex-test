<?php
namespace App\Domain\User\Data;

use App\Domain\Role\Data\RoleItem;
use App\Domain\SignatureCredit\Data\SignatureCreditItem;

final class UserItem {
  public int $id;
  public string $uuid;
  public string $rfc;
  public ?string $first_name;
  public ?string $last_name;
  public ?string $mother_last_name;
  public string $full_name;
  public string $username;
  public string $email;
  public ?string $phone;
  public string $password;
  // public bool $status;
  public RoleItem | int $role;
  public int $credit_id;
  public ?SignatureCreditItem $signature_credit;
  public ?int $client_id = null;

  public bool $pass_reset;
  public mixed $created_at;

}