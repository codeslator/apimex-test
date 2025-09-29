<?php

namespace App\Domain\Document\Data;
 
enum DocumentStatus: string {
  case CREATED = 'CREATED';
  case REVIEW = 'REVIEW';
  case APPROVED = 'APPROVED';
  case REJECTED = 'REJECTED';
  case SIGNED_PENDING = 'SIGNED_PENDING';
  case SIGNED = 'SIGNED';
  case FINISHED = 'FINISHED';
  case DELETED = 'DELETED';
  case OTHER = 'OTHER';
}