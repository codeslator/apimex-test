<?php

namespace App\Factory;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ConstraintFactory
{
    public function collection(array $fields = null): Assert\Collection
    {
        return new Assert\Collection($fields);
    }

    public function required(array $options): Assert\Required
    {
        return new Assert\Required($options);
    }

    public function optional(array $options): Assert\Optional
    {
        return new Assert\Optional($options);
    }

    public function notBlank(): Assert\NotBlank
    {
        return new Assert\NotBlank();
    }

    public function length(int $min = null, int $max = null): Assert\Length
    {
        return new Assert\Length(['min' => $min, 'max' => $max]);
    }

    public function positive(): Assert\Positive
    {
        return new Assert\Positive();
    }

    public function positiveOrZero(): Assert\PositiveOrZero
    {
        return new Assert\PositiveOrZero();
    }

    public function number(): Assert\Callback
    {
        return new Assert\Callback(function ($val, ExecutionContextInterface $context) {
            if (!filter_var($val, FILTER_VALIDATE_INT)) {
                $context->addViolation('This value is not a NUMBER value, please enter a NUMBER.');
            }
        });
    }

    public function bolean(): Assert\Callback
    {
        return new Assert\Callback(function ($val, ExecutionContextInterface $context) {
            if (!is_bool($val)) {
                $context->addViolation($val . ' This value is not a BOLEAN value, please enter a BOLEAN.');
            }
        });
    }

    public function email(): Assert\Callback
    {
        return new Assert\Callback(function ($email, ExecutionContextInterface $context) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $context->addViolation('This value is not a valid email address.');
            }
        });
    }

    public function signersLimit(int $min = null, int $max = null): Assert\Callback
    {
        return new Assert\Callback(function ($signers, ExecutionContextInterface $context) use ($min, $max) {
            $count = count($signers);
            if ($count < $min || $count > $max) {
                $context->addViolation('There must be at least ' . $min . ' participant and a maximum of ' . $max . ' participants.');
            }
        });
    }

    public function allNotLegal(bool $allSignerIsNotLegal): Assert\Callback
    {
        return new Assert\Callback(function ($signers, ExecutionContextInterface $context) use ($allSignerIsNotLegal) {

            if (!$allSignerIsNotLegal) {
                $context->addViolation('There must be at least 1 non-legal participant');
            }
        });
    }

    public function portion($total): Assert\Callback
    {
        $total = $total;
        return new Assert\Callback(function ($portion, ExecutionContextInterface $context) use ($total) {
            // $val = floatval($portion);
            // if ($val > 0 && $val != 50 && $val != 100) {
            //     $context->addViolation('The part ' . $portion . ' you have entered is not valid, it must be 50 or 100.');
            // }

            // if ($signers  == 1 && $total != 100) {
            //     $context->addViolation('The portion is not valid, the total sum of the values per participant must be 100%.');
            // }

            if ($total != 100) {
                $context->addViolation('The total of portions is: ' . $total . '. The portion is not valid, the total sum of the values per participant must be 100.');
            }
        });
    }

    public function rfc(): Assert\Callback
    {
        return new Assert\Callback(function ($rfc, ExecutionContextInterface $context) {

            $pattern = "/^([A-ZÑ&]{3,4})(?:- )?(\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01]))(?:- )?([A-Z\d]{2})([A\d])$/";

            if (!preg_match($pattern, $rfc)) {
                $context->addViolation('The rfc you entered is not supported. Please check your rfc');
            }

            if ((empty($rfc)) || strlen($rfc) < 3) {
                $context->addViolation('RFC empty or with less than 3 characters.');
            }
        });
    }

    public function curp(): Assert\Callback
    {
        return new Assert\Callback(function ($curp, ExecutionContextInterface $context) {
            if (empty($curp)) {
                $context->addViolation('El CURP no puede estar vacío.');
                return;
            }

            $curp = strtoupper(trim($curp));

            $regex = '/^[A-Z][AEIOUX][A-Z]{2}'
                . '\d{2}(0[1-9]|1[0-2])'            // Mes
                . '(0[1-9]|[12]\d|3[01])'           // Día
                . '[HM]'                            // Sexo
                . '(AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TL|TS|VZ|YN|ZS|NE)'
                . '[B-DF-HJ-NP-TV-Z]{3}'            // Consonantes internas
                . '[A-Z0-9]'                        // Homoclave
                . '\d$/';                           // Dígito verificador

            if (!preg_match($regex, $curp)) {
                $context->addViolation('El CURP no tiene un formato válido.');
                return;
            }

            // Validar fecha real
            $year = intval(substr($curp, 4, 2));
            $month = intval(substr($curp, 6, 2));
            $day = intval(substr($curp, 8, 2));

            // Determinar siglo (>= año actual => 1900, si no 2000) heurística simple
            $currentYY = intval(date('y'));
            $fullYear = ($year <= $currentYY) ? 2000 + $year : 1900 + $year;

            if (!checkdate($month, $day, $fullYear)) {
                $context->addViolation('La fecha de nacimiento en el CURP no es válida.');
            }
        });
    }

    public function mxPhone(): Assert\Callback
    {
        return new Assert\Callback(function ($phone, ExecutionContextInterface $context) {
            if (empty($phone)) {
                $context->addViolation('El número telefónico no puede estar vacío.');
                return;
            }

            $phone = trim($phone);

            // Formato solicitado explícitamente: +521 seguido de 10 dígitos (total 14 caracteres)
            $patternEstricto = '/^\+521\d{10}$/';

            if (!preg_match($patternEstricto, $phone)) {
                $context->addViolation('El número debe tener el formato +521XXXXXXXXXX (10 dígitos después de +521).');
            }
        });
    }
}
