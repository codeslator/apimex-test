<?php

namespace App\Domain\ClientApiKey\Data;
 
enum ClientApiKeyEnvironment: string {
  case PRODUCTION = 'PRODUCTION';
  case STAGING = 'STAGING';
  case DEVELOPMENT = 'DEVELOPMENT';
}