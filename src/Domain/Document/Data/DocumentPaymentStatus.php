<?php

namespace App\Domain\Document\Data;
 
enum DocumentPaymentStatus: string {
  case PAIDOUT = 'PAIDOUT';
  case PENDING = 'PENDING';
}