<?php
namespace CondMan\Domain\Entities;

use CondMan\Domain\Interfaces\RevenueInterface;
use CondMan\Domain\Validators\RevenueValidator;
use DateTime;

class Revenue implements RevenueInterface {
    private ?int $id = null;
    private int $condominiumId;
    private string $source;
    private string $category;
    private float $baseAmount;
    private float $projectedAmount;
    private float $totalAmount;
    private string $receiptStatus;
    private DateTime $expectedDate;
    private ?DateTime $receiptDate = null;
    private bool $confirmed = false;
    private bool $isRecurring = false;
    private ?string $recurrenceFrequency = null;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->condominiumId = $data['condominium_id'] ?? 0;
        $this->source = $data['source'] ?? '';
        $this->category = $data['category'] ?? '';
        $this->baseAmount = $data['base_amount'] ?? 0.0;
        $this->projectedAmount = $data['projected_amount'] ?? 0.0;
        $this->totalAmount = $data['total_amount'] ?? 0.0;
        $this->receiptStatus = $data['receipt_status'] ?? 'pending';
        $this->expectedDate = $data['expected_date'] instanceof DateTime 
            ? $data['expected_date'] 
            : new DateTime($data['expected_date'] ?? 'now');
        $this->receiptDate = $data['receipt_date'] instanceof DateTime 
            ? $data['receipt_date'] 
            : ($data['receipt_date'] ? new DateTime($data['receipt_date']) : null);
        $this->confirmed = $data['confirmed'] ?? false;
        $this->isRecurring = $data['is_recurring'] ?? false;
        $this->recurrenceFrequency = $data['recurrence_frequency'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getSource(): string {
        return $this->source;
    }

    public function setSource(string $source): void {
        $this->source = $source;
    }

    public function getCategory(): string {
        return $this->category;
    }

    public function setCategory(string $category): void {
        $this->category = $category;
    }

    public function calculateTotalAmount(): float {
        return $this->baseAmount;
    }

    public function isConfirmed(): bool {
        return $this->confirmed;
    }

    public function getReceiptStatus(): string {
        return $this->receiptStatus;
    }

    public function applyDiscount(float $discountPercentage): float {
        $discountAmount = $this->totalAmount * ($discountPercentage / 100);
        return $this->totalAmount - $discountAmount;
    }

    public function isRecurring(): bool {
        return $this->isRecurring;
    }

    public function getRecurrenceFrequency(): string {
        return $this->recurrenceFrequency ?? '';
    }

    public function calculateProjectedAmount(): float {
        return $this->projectedAmount;
    }

    public function getCondominiumId(): int {
        return $this->condominiumId;
    }

    public function setCondominiumId(int $condominiumId): void {
        $this->condominiumId = $condominiumId;
    }

    public function getBaseAmount(): float {
        return $this->baseAmount;
    }

    public function setBaseAmount(float $baseAmount): void {
        $this->baseAmount = $baseAmount;
    }

    public function getProjectedAmount(): float {
        return $this->projectedAmount;
    }

    public function setProjectedAmount(float $projectedAmount): void {
        $this->projectedAmount = $projectedAmount;
    }

    public function getExpectedDate(): DateTime {
        return $this->expectedDate;
    }

    public function setExpectedDate(DateTime $expectedDate): void {
        $this->expectedDate = $expectedDate;
    }

    public function getReceiptDate(): ?DateTime {
        return $this->receiptDate;
    }

    public function setReceiptDate(?DateTime $receiptDate): void {
        $this->receiptDate = $receiptDate;
    }
}
