<?php

namespace App\Domain\Signature\Utilities;

use App\Domain\Signature\Repository\SignatureRepository;
use App\Factory\ConstraintFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validation;

final class SignatureValidator
{
  private SignatureRepository $repository;

  public function __construct(
    SignatureRepository $repository,
    )
  {
    $this->repository = $repository;
  }

  public function validateSignature(array $data, ?int $id): void
  {
    $constraint = $this->createSignatureConstraints();

    $violations = Validation::createValidator()->validate($data, $constraint);
    if ($violations->count()) {
      throw new ValidationFailedException('Please check your input', $violations);
    }
  }

  public function validateSigners(array $data): void
  {
    foreach ($data['signers'] as $signer) {
      $violations = Validation::createValidator()->validate($signer, $this->createSignatureConstraints());
      if ($violations->count()) {
        throw new ValidationFailedException('Please check your input signers', $violations);
      }
    }
  }

  private function createSignatureConstraints(): Constraint
  {
    $constraint = new ConstraintFactory();

    return $constraint->collection(
      [
        'rfc' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 20),
            $constraint->rfc(),
          ]
        ),
        'first_name' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 255),
          ]
        ),
        'last_name' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 255),
          ]
        ),
        'mother_last_name' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 255),
          ]
        ),
        'email' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 255),
          ]
        ),
        'role' => $constraint->required(
          [
            $constraint->notBlank(),
          ]
        ),
        'signer_type' => $constraint->required(
          [
            $constraint->notBlank(),
            $constraint->length(null, 255),
          ]
        ),
        'portion' => $constraint->required(
          [
            $constraint->notBlank(),
          ]
        ),
        'require_video' => $constraint->optional(
          [
            $constraint->bolean(),
          ]
        ),
      ]
    );
  }

  public function validateDate(string $date): string|null
  {
    $date = trim($date);

    $abbrEsMonths = [
      'Ene' => 1,
      'Feb' => 2,
      'Mar' => 3,
      'Abr' => 4,
      'May' => 5,
      'Jun' => 6,
      'Jul' => 7,
      'Ago' => 8,
      'Sep' => 9,
      'Oct' => 10,
      'Nov' => 11,
      'Dic' => 12
    ];

    // Definimos los formatos aceptados en orden de prioridad
    $formats = [
      'd/m/Y' => '/^\d{2}\/\d{2}\/\d{4}$/',      // dd/MM/yyyy
      'd-y-Y' => '/^\d{2}-\d{2}-\d{4}$/',        // dd-yy-yyyy
      'd M Y' => '/^\d{2} [A-Za-z]{3} \d{4}$/'  // dd MMM yyyy
    ];

    foreach ($formats as $format => $pattern) {
      if (preg_match($pattern, $date)) {
        // Para el formato con nombre de mes, normalizamos el nombre

        if ($format === 'd M Y') {
          $parts = explode(' ', $date);
          $month = ucfirst(strtolower($parts[1]));

          // Verificamos si el mes existe en español
          if (!isset($abbrEsMonths[$month])) {
            return null;
          }

          // Creamos una fecha normalizada para el parser
          $newDate = sprintf(
            '%02d-%02d-%04d',
            $parts[0],
            $abbrEsMonths[$month],
            $parts[2]
          );

          $dateTime = \DateTime::createFromFormat('d-m-Y', $newDate);
        } else {
          $dateTime = \DateTime::createFromFormat($format, $date);
        }

        if ($dateTime && $dateTime->format($format)) {
          return $dateTime->format('Y-m-d');
        }
      }
    }

    return null;
  }

  public function mapDocumentData(array $data): array
  {
    $userInfo = [];
    $idInfo = [];
    $firstName = '';
    $lastName = '';
    $middleName = '';
    $birthDate = '';
    $curp = '';
    // $rfc = '';

    $validMexicanTemplates = [
      'Mexico - ID Card (Voter) - 2020_UC - Horizontal [v9]',
      'Mexico - ID Card (Voter) - 2018 - Horizontal [v3]',
      'Mexico - ID Card (Voter) - 2013 - Horizontal [v4]'
    ];
    $templateName = $data['templateInfo']['templateName'];

    foreach ($data['scannedValues']['groups'] as $group) {
      if ($group['groupKey'] == 'userInfo') {
        $userInfo = $group['fields'];
      }
      if ($group['groupKey'] == 'idInfo') {
        $idInfo = $group['fields'];
      }
    }

    if (!empty($userInfo)) {
      foreach ($userInfo as $field) {
        if ($field['fieldKey'] == 'firstName') {
          $firstName = ucwords(strtolower($field['value']));
        }
        if ($field['fieldKey'] == 'lastName') {
          $lastName = ucwords(strtolower($field['value']));
        }
        if ($field['fieldKey'] == 'middleName') {
          $middleName = ucwords(strtolower($field['value']));
        }
        if ($field['fieldKey'] == 'dateOfBirth') {
          $birthDate = $this->validateDate($field['value']);
        }
        if ($field['fieldKey'] == 'fullName') {
          return [];
        }
      }
    }

    if (!empty($idInfo)) {
      foreach ($idInfo as $field) {
        if ($field['fieldKey'] == 'idNumber2') {
          $curp = $field['value'];
        }
        // if ($group['fieldKey'] == 'idNumber2') {
        //   $rfc = $field['value'];
        // }
      }
    }


    if (in_array($templateName, $validMexicanTemplates)) {
      return [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'mother_last_name' => $middleName,
        'curp' => $curp,
        'birth_date' => $birthDate,
        // 'rfc' => $rfc,
      ];
    }

    return [
      'first_name' => $firstName,
      'last_name' => $lastName,
      'mother_last_name' => $middleName,
      'birth_date' => $birthDate,
    ];
  }
}
