<?php
namespace CondMan\Domain\Entities;

use CondMan\Domain\Interfaces\CondominiumInterface;
use CondMan\Domain\Validators\CondominiumValidator;

class Condominium implements CondominiumInterface {
    private ?int $id = null;
    private string $name = '';
    private string $cnpj = '';
    private string $address = '';
    private int $totalUnits = 0;
    private bool $active = true;

    public function getId(): ?int {
        return $this->id;
    }

    public function setName(string $name): void {
        $this->name = trim($name);
    }

    public function getName(): string {
        return $this->name;
    }

    public function setCnpj(string $cnpj): void {
        $this->cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    }

    public function getCnpj(): string {
        return $this->cnpj;
    }

    public function setAddress(string $address): void {
        $this->address = trim($address);
    }

    public function getAddress(): string {
        return $this->address;
    }

    public function setTotalUnits(int $totalUnits): void {
        $this->totalUnits = max(0, $totalUnits);
    }

    public function getTotalUnits(): int {
        return $this->totalUnits;
    }

    public function isActive(): bool {
        return $this->active;
    }

    public function setActive(bool $active): void {
        $this->active = $active;
    }

    public function validate(): bool {
        $validator = new CondominiumValidator($this);
        return $validator->validate();
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'cnpj' => $this->cnpj,
            'address' => $this->address,
            'total_units' => $this->totalUnits,
            'active' => $this->active
        ];
    }
}
