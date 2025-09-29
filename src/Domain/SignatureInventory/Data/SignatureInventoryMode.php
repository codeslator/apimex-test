<?php

namespace App\Domain\SignatureInventory\Data;
 
enum SignatureInventoryMode: string {
  case INCREMENT = 'increment';
  case DECREMENT = 'decrement';
}