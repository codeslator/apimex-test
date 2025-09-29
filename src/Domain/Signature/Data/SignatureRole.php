<?php

namespace App\Domain\Signature\Data;
 
enum SignatureRole: string {
  case SIGNER = 'SIGNER';
  case PAYER = 'PAYER';
  case SIGNER_PAYER = 'SIGNER_PAYER';
}