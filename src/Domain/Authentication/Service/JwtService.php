<?php

namespace App\Domain\Authentication\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Exception;


final class JwtService
{
  private string $secret;
  private string $algo;
  private int $expiration; // segundos

  public function __construct(
    string $algo = 'HS256',
    int $expiration = 3600
  ) {
    $this->secret = $_SERVER['SECRET_KEY'];
    $this->algo = $algo;
    $this->expiration = $expiration;
  }

  public function generateToken(array $payload): string
  {
    $issuedAt = time();
    $payload['iat'] = $issuedAt;
    $payload['exp'] = $issuedAt + $this->expiration * 12;

    return JWT::encode($payload, $this->secret, $this->algo);
  }

  public function validate(string $token): array
  {
    try {
      return (array) JWT::decode($token, new Key($this->secret, $this->algo));
    } catch (ExpiredException $e) {
      throw new Exception('Token expired');
    } catch (Exception $e) {
      throw new Exception('Invalid token');
    }
  }
}
