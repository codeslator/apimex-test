<?php

namespace App\Domain\Document\Data;
 
enum DocumentOwnerType: string {
  case NATURAL = 'NATURAL';
  case LEGAL = 'LEGAL';
}