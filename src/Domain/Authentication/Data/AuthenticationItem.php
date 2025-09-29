<?php

namespace App\Domain\Authentication\Data;
use App\Domain\User\Data\UserItem;

final class AuthenticationItem {
  public string $token;
  public UserItem $user;  
  public array $permissions;
}