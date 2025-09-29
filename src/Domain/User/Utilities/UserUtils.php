<?php

namespace App\Domain\User\Utilities;

use App\Domain\User\Data\UserItem;

final class UserUtils
{
  public function __construct() {}

  public function capitalize(mixed $value): ?string
  {
    if (!is_string($value) || trim($value) === '' || $value === null) {
      return null;
    }
    return ucwords(strtolower($value));
  }

  public function transform(array $row): UserItem
  {
    $user = new UserItem();
    $user->id = $row['id'];
    $user->uuid = $row['uuid'];
    $user->rfc = $row['rfc'];
    $user->first_name = $row['first_name'];
    $user->last_name = $row['last_name'];
    $user->mother_last_name = $row['mother_last_name'];
    $user->email = $row['email'];
    $user->phone = $row['phone'];
    $user->role = $row['role_id'];
    $user->created_at = $row['created_at'];
    return $user;
  }

  public function transformOwner(array $row): UserItem
  {
    $user = new UserItem();
    $user->id = $row['id'];
    $user->uuid = $row['uuid'];
    $user->rfc = $row['rfc'];
    $user->first_name = $row['first_name'];
    $user->last_name = $row['last_name'];
    $user->mother_last_name = $row['mother_last_name'];
    $user->email = $row['email'];
    $user->phone = $row['phone'];
    $user->role = $row['role_id'];
    $user->created_at = $row['created_at'];
    return $user;
  }
}
