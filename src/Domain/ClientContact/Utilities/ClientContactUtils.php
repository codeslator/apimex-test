<?php

namespace App\Domain\ClientContact\Utilities;
use App\Domain\ClientContact\Data\ClientContactItem;
final class ClientContactUtils
{

  public function __construct() {}

  public function capitalize(mixed $value): ?string
  {
    if (!isset($value) || !is_string($value) || trim($value) === '' || $value === null) {
      return null;
    }
    return ucwords(strtolower($value));
  }

  public function transform(array $row): ClientContactItem
  {
    $contact = new ClientContactItem();
    $contact->id = (int) $row['id'];
    $contact->uuid = $row['uuid'];
    $contact->rfc = $row['rfc'];
    $contact->curp = $row['curp'];
    $contact->first_name = $row['first_name'];
    $contact->last_name = $row['last_name'];
    $contact->mother_last_name = $row['mother_last_name'];
    $contact->full_name = $row['full_name'];
    $contact->email = $row['email'];
    $contact->phone = $row['phone'];
    $contact->created_at = $row['created_at'];
    $contact->updated_at = $row['updated_at'];
    return $contact;
  }
}
