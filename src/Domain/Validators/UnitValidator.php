<?php
namespace CondMan\Domain\Validators;

use CondMan\Domain\Interfaces\UnitInterface;

class UnitValidator {
    private UnitInterface $unit;

    public function __construct(UnitInterface $unit) {
        $this->unit = $unit;
    }

    public function validate(): bool {
        return $this->validateCondominiumId() && 
               $this->validateNumber() && 
               $this->validateType() && 
               $this->validateArea() && 
               $this->validateFraction() && 
               $this->validateStatus();
    }

    private function validateCondominiumId(): bool {
        return $this->unit->getCondominiumId() > 0;
    }

    private function validateNumber(): bool {
        $number = $this->unit->getNumber();
        return !empty($number) && 
               mb_strlen($number) >= 1 && 
               mb_strlen($number) <= 10;
    }

    private function validateType(): bool {
        $validTypes = ['residential', 'commercial', 'parking'];
        return in_array($this->unit->getType(), $validTypes);
    }

    private function validateArea(): bool {
        $area = $this->unit->getArea();
        return $area === null || ($area > 0 && $area <= 1000);
    }

    private function validateFraction(): bool {
        $fraction = $this->unit->getFraction();
        return $fraction === null || ($fraction > 0 && $fraction <= 1);
    }

    private function validateStatus(): bool {
        $validStatuses = ['active', 'inactive', 'maintenance'];
        return in_array($this->unit->getStatus(), $validStatuses);
    }
}
