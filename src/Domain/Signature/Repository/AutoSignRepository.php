<?php

namespace App\Domain\Signature\Repository;

use App\Factory\AutoSignFactory;
use DomainException;

final class AutoSignRepository
{
  private $curlFactory;

  public function __construct()
  {
    $this->curlFactory = new AutoSignFactory();
  }

  public function signDocument(string $identifier, array $signers, string $document)
  {
    try {
      $resp = $this->curlFactory->generateSignedDocument($identifier, $signers, $document);
      return $resp;
    } catch (\Exception $e) {
      throw new DomainException('Error en ejecucion del curl aut2Sign ' . sprintf($e->getMessage()));
    }
  }
}
