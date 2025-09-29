<?php

namespace App\Domain\DocumentType\Data;
 
enum DocumentType: string {
  case GENERIC = "GENERIC";
  case REAL_STATE = "REAL_STATE";
  case LABOR = "LABOR";
  case PERSONAL = "PERSONAL";
  case POWER = "POWER";
  case COMPANY = "COMPANY";
  case SOCIETY = "SOCIETY";
  case VEHICLE = "VEHICLE";
}