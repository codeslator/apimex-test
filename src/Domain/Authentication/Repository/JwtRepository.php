<?php

namespace App\Domain\Authentication\Repository;

use App\Factory\PdoFactory;

final class JwtRepository
{
  private PdoFactory $pdoFactory;

  public function __construct(PdoFactory $pdoFactory)
  {
    $this->pdoFactory = $pdoFactory;
  }

  public function loginByCredentials(array $data): mixed
  {
    return $this->pdoFactory->login($data['email'], hash('sha512', $data['password']));
  }

  public function resetPassword(string $email, string $password): void
  {
    $data = [
      'password' => $password,
      'pass_reset' => true
    ];
    $this->pdoFactory->updateBy('users', 'email', $email, $data);
  }

  
}