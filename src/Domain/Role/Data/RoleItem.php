<?php
namespace App\Domain\Role\Data;


final class RoleItem {
  public int $id;
  public string $code;
  public string $name;
  public ?string $description;
  public bool $is_active;
  public array $permissions;

}