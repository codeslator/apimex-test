<?php

namespace App\Domain\SignatureInventory\Data;
 
enum SignatureInventorySource: string {
  case PSC_WORLD = 'PSC_WORLD';
  case OTHER = 'OTHER';
}