<?php

namespace App\Domain\Role\Data;
 
enum RoleCode: string {
  case ADMIN = 'ADMIN';
  case USER = 'USER';
  case API_INTEGRATION = 'API_INTEGRATION';
}