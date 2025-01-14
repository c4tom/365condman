<?php
namespace CondMan\Domain\Entities;

use CondMan\Domain\Interfaces\ExpenseInterface;
use CondMan\Domain\Validators\ExpenseValidator;
use DateTime;

class Expense implements ExpenseInterface {
    private ?int $id = null;
    private int $condominiumId;
    private string $category;
    private string $supplier;
    private float $baseAmount;
    private float $taxAmount;
    private float $totalAmount;
    private string $paymentStatus;
    private DateTime $dueDate;
    private ?DateTime $paymentDate = null;
    private bool $isRecurring = false;
    private ?string $recurrenceFrequency = null;
    private bool $approved = false;
    private ?string $approvedBy = null;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->condominiumId = $data['condominium_id'] ?? 0;
        $this->category = $data['category'] ?? '';
        $this->supplier = $data['supplier'] ?? '';
        $this->baseAmount = $data['base_amount'] ?? 0.0;
        $this->taxAmount = $data['tax_amount'] ?? 0.0;
        $this->totalAmount = $data['total_amount'] ?? 0.0;
        $this->paymentStatus = $data['payment_status'] ?? 'pending';
        $this->dueDate = $data['due_date'] instanceof DateTime 
            ? $data['due_date'] 
            : new DateTime($data['due_date'] ?? 'now');
        $this->paymentDate = $data['payment_date'] instanceof DateTime 
            ? $data['payment_date'] 
            : ($data['payment_date'] ? new DateTime($data['payment_date']) : null);
        $this->isRecurring = $data['is_recurring'] ?? false;
        $this->recurrenceFrequency = $data['recurrence_frequency'] ?? null;
        $this->approved = $data['approved'] ?? false;
        $this->approvedBy = $data['approved_by'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getCategory(): string {
        return $this->category;
    }

    public function setCategory(string $category): void {
        $this->category = $category;
    }

    public function getSupplier(): string {
        return $this->supplier;
    }

    public function setSupplier(string $supplier): void {
        $this->supplier = $supplier;
    }

    public function calculateTotalAmount(): float {
        return $this->baseAmount + $this->taxAmount;
    }

    public function isApproved(): bool {
        return $this->approved;
    }

    public function getPaymentStatus(): string {
        return $this->paymentStatus;
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

    public function getTaxAmount(): float {
        return $this->taxAmount;
    }

    public function setTaxAmount(float $taxAmount): void {
        $this->taxAmount = $taxAmount;
    }

    public function getDueDate(): DateTime {
        return $this->dueDate;
    }

    public function setDueDate(DateTime $dueDate): void {
        $this->dueDate = $dueDate;
    }

    public function getPaymentDate(): ?DateTime {
        return $this->paymentDate;
    }

    public function setPaymentDate(?DateTime $paymentDate): void {
        $this->paymentDate = $paymentDate;
    }

    public function getApprovedBy(): ?string {
        return $this->approvedBy;
    }

    public function setApprovedBy(?string $approvedBy): void {
        $this->approvedBy = $approvedBy;
    }
}
