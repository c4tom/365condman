<?php
namespace CondMan\Domain\Entities;

use CondMan\Domain\Interfaces\InvoiceInterface;
use CondMan\Domain\Validators\InvoiceValidator;
use DateTime;

class Invoice implements InvoiceInterface {
    private ?int $id = null;
    private int $condominiumId;
    private int $unitId;
    private string $referenceMonth;
    private string $referenceYear;
    private DateTime $dueDate;
    private float $totalAmount = 0.0;
    private float $totalPaid = 0.0;
    private string $status = 'pending';
    private array $items = [];
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->condominiumId = $data['condominium_id'] ?? 0;
        $this->unitId = $data['unit_id'] ?? 0;
        $this->referenceMonth = $data['reference_month'] ?? date('m');
        $this->referenceYear = $data['reference_year'] ?? date('Y');
        $this->dueDate = $data['due_date'] instanceof DateTime 
            ? $data['due_date'] 
            : new DateTime($data['due_date'] ?? 'now');
        $this->totalAmount = $data['total_amount'] ?? 0.0;
        $this->totalPaid = $data['total_paid'] ?? 0.0;
        $this->status = $data['status'] ?? 'pending';
        $this->items = $data['items'] ?? [];
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

    public function setUnitId(int $unitId): void {
        $this->unitId = $unitId;
    }

    public function getUnitId(): int {
        return $this->unitId;
    }

    public function setReferenceMonth(string $referenceMonth): void {
        $this->referenceMonth = str_pad($referenceMonth, 2, '0', STR_PAD_LEFT);
    }

    public function getReferenceMonth(): string {
        return $this->referenceMonth;
    }

    public function setReferenceYear(string $referenceYear): void {
        $this->referenceYear = $referenceYear;
    }

    public function getReferenceYear(): string {
        return $this->referenceYear;
    }

    public function setDueDate(DateTime $dueDate): void {
        $this->dueDate = $dueDate;
    }

    public function getDueDate(): DateTime {
        return $this->dueDate;
    }

    public function setTotalAmount(float $totalAmount): void {
        $this->totalAmount = max(0, $totalAmount);
    }

    public function getTotalAmount(): float {
        return $this->totalAmount;
    }

    public function setTotalPaid(float $totalPaid): void {
        $this->totalPaid = max(0, $totalPaid);
    }

    public function getTotalPaid(): float {
        return $this->totalPaid;
    }

    public function setStatus(string $status): void {
        $validStatuses = ['pending', 'paid', 'overdue', 'partial', 'canceled'];
        $this->status = in_array($status, $validStatuses) ? $status : 'pending';
        $this->updatedAt = current_time('mysql');
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function addItem(array $item): void {
        $requiredKeys = ['description', 'amount', 'quantity'];
        if (count(array_intersect_key(array_flip($requiredKeys), $item)) === count($requiredKeys)) {
            $this->items[] = $item;
            $this->totalAmount += $item['amount'] * $item['quantity'];
        }
    }

    public function getItems(): array {
        return $this->items;
    }

    public function validate(): bool {
        $validator = new InvoiceValidator($this);
        return $validator->validate();
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'condominium_id' => $this->condominiumId,
            'unit_id' => $this->unitId,
            'reference_month' => $this->referenceMonth,
            'reference_year' => $this->referenceYear,
            'due_date' => $this->dueDate->format('Y-m-d'),
            'total_amount' => $this->totalAmount,
            'total_paid' => $this->totalPaid,
            'status' => $this->status,
            'items' => $this->items,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}
