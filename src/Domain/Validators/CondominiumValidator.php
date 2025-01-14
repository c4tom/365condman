<?php
namespace CondMan\Domain\Validators;

use CondMan\Domain\Interfaces\CondominiumInterface;

class CondominiumValidator {
    private CondominiumInterface $condominium;

    public function __construct(CondominiumInterface $condominium) {
        $this->condominium = $condominium;
    }

    public function validate(): bool {
        return $this->validateName() && 
               $this->validateCnpj() && 
               $this->validateAddress() && 
               $this->validateTotalUnits();
    }

    private function validateName(): bool {
        $name = $this->condominium->getName();
        return !empty($name) && 
               mb_strlen($name) >= 3 && 
               mb_strlen($name) <= 100;
    }

    private function validateCnpj(): bool {
        $cnpj = $this->condominium->getCnpj();
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        // Validar se tem 14 dígitos
        if (strlen($cnpj) !== 14) {
            return false;
        }

        // Validar dígitos verificadores
        $sum1 = 0;
        $sum2 = 0;
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        for ($i = 0; $i < 12; $i++) {
            $sum1 += $cnpj[$i] * $weights1[$i];
        }

        $digit1 = $sum1 % 11 < 2 ? 0 : 11 - ($sum1 % 11);

        if ($digit1 != $cnpj[12]) {
            return false;
        }

        for ($i = 0; $i < 13; $i++) {
            $sum2 += $cnpj[$i] * $weights2[$i];
        }

        $digit2 = $sum2 % 11 < 2 ? 0 : 11 - ($sum2 % 11);

        return $digit2 == $cnpj[13];
    }

    private function validateAddress(): bool {
        $address = $this->condominium->getAddress();
        return !empty($address) && 
               mb_strlen($address) >= 10 && 
               mb_strlen($address) <= 200;
    }

    private function validateTotalUnits(): bool {
        $totalUnits = $this->condominium->getTotalUnits();
        return $totalUnits >= 0 && $totalUnits <= 1000;
    }
}
