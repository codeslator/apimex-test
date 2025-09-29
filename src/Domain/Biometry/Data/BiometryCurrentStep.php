<?php

namespace App\Domain\Biometry\Data;
 
enum BiometryCurrentStep: string {
  case Email = 'Email';
  case Photo = 'Photo';
  case Biometry = 'Biometry';
  case Video = 'Video';
  case Signature = 'Signature';
  case Finish = 'Finish';
}