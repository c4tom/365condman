<?php
namespace CondMan\Domain\Entities;

use DateTime;
use JsonSerializable;

class FinancialTransaction implements JsonSerializable {
    private ?int $id;
    private int $condominiumId;
    private float $amount;
    private string $type;
    private string $category;
    private string $description;
    private DateTime $date;
    private string $status;
    private ?int $invoiceId;
    private ?int $paymentId;
    private ?string $reference;
    private ?array $metadata;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->condominiumId = $data['condominium_id'] ?? 0;
        $this->amount = $data['amount'] ?? 0.0;
        $this->type = $data['type'] ?? 'other';
        $this->category = $data['category'] ?? 'uncategorized';
        $this->description = $data['description'] ?? '';
        $this->date = $data['date'] ?? new DateTime();
        $this->status = $data['status'] ?? 'pending';
        $this->invoiceId = $data['invoice_id'] ?? null;
        $this->paymentId = $data['payment_id'] ?? null;
        $this->reference = $data['reference'] ?? null;
        $this->metadata = $data['metadata'] ?? [];
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getCondominiumId(): int { return $this->condominiumId; }
    public function getAmount(): float { return $this->amount; }
    public function getType(): string { return $this->type; }
    public function getCategory(): string { return $this->category; }
    public function getDescription(): string { return $this->description; }
    public function getDate(): DateTime { return $this->date; }
    public function getStatus(): string { return $this->status; }
    public function getInvoiceId(): ?int { return $this->invoiceId; }
    public function getPaymentId(): ?int { return $this->paymentId; }
    public function getReference(): ?string { return $this->reference; }
    public function getMetadata(): ?array { return $this->metadata; }

    // Setters
    public function setStatus(string $status): void { $this->status = $status; }
    public function setMetadata(array $metadata): void { $this->metadata = $metadata; }

    // Validação básica
    public function isValid(): bool {
        return $this->amount >= 0 
            && $this->condominiumId > 0 
            && in_array($this->status, ['pending', 'completed', 'canceled', 'refunded']);
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'condominium_id' => $this->condominiumId,
            'amount' => $this->amount,
            'type' => $this->type,
            'category' => $this->category,
            'description' => $this->description,
            'date' => $this->date->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'invoice_id' => $this->invoiceId,
            'payment_id' => $this->paymentId,
            'reference' => $this->reference,
            'metadata' => $this->metadata
        ];
    }
}
