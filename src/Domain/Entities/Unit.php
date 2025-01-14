<?php
namespace CondMan\Domain\Entities;

use CondMan\Domain\Interfaces\UnitInterface;
use CondMan\Domain\Validators\UnitValidator;

class Unit implements UnitInterface {
    private ?int $id = null;
    private int $condominiumId;
    private ?string $block = null;
    private string $number = '';
    private string $type = 'residential';
    private ?float $area = null;
    private ?float $fraction = null;
    private string $status = 'active';
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->condominiumId = $data['condominium_id'] ?? 0;
        $this->block = $data['block'] ?? null;
        $this->number = $data['number'] ?? '';
        $this->type = $data['type'] ?? 'residential';
        $this->area = $data['area'] ?? null;
        $this->fraction = $data['fraction'] ?? null;
        $this->status = $data['status'] ?? 'active';
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function setCondominiumId(int $condominiumId): void {
        $this->condominiumId = $condominiumId;
    }

    public function getCondominiumId(): int {
        return $this->condominiumId;
    }

    public function setBlock(?string $block): void {
        $this->block = $block;
    }

    public function getBlock(): ?string {
        return $this->block;
    }

    public function setNumber(string $number): void {
        $this->number = trim($number);
    }

    public function getNumber(): string {
        return $this->number;
    }

    public function setType(string $type): void {
        $this->type = in_array($type, ['residential', 'commercial', 'parking']) ? $type : 'residential';
    }

    public function getType(): string {
        return $this->type;
    }

    public function setArea(?float $area): void {
        $this->area = $area > 0 ? $area : null;
    }

    public function getArea(): ?float {
        return $this->area;
    }

    public function setFraction(?float $fraction): void {
        $this->fraction = $fraction > 0 ? $fraction : null;
    }

    public function getFraction(): ?float {
        return $this->fraction;
    }

    public function setStatus(string $status): void {
        $this->status = in_array($status, ['active', 'inactive', 'maintenance']) ? $status : 'active';
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function validate(): bool {
        $validator = new UnitValidator($this);
        return $validator->validate();
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'condominium_id' => $this->condominiumId,
            'block' => $this->block,
            'number' => $this->number,
            'type' => $this->type,
            'area' => $this->area,
            'fraction' => $this->fraction,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}
