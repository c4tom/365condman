<?php
namespace CondMan\Domain\Entities;

use DateTime;

class Payment {
    private ?int $id = null;
    private int $invoiceId;
    private int $condominiumId;
    private float $amount;
    private string $paymentMethod;
    private string $status;
    private ?string $transactionId = null;
    private ?string $gatewayReference = null;
    private DateTime $paymentDate;
    private ?string $receiptUrl = null;
    private ?float $processingFee = null;
    private ?string $errorMessage = null;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->invoiceId = $data['invoice_id'] ?? 0;
        $this->condominiumId = $data['condominium_id'] ?? 0;
        $this->amount = $data['amount'] ?? 0.0;
        $this->paymentMethod = $data['payment_method'] ?? '';
        $this->status = $data['status'] ?? 'pending';
        $this->transactionId = $data['transaction_id'] ?? null;
        $this->gatewayReference = $data['gateway_reference'] ?? null;
        $this->paymentDate = $data['payment_date'] instanceof DateTime 
            ? $data['payment_date'] 
            : new DateTime($data['payment_date'] ?? 'now');
        $this->receiptUrl = $data['receipt_url'] ?? null;
        $this->processingFee = $data['processing_fee'] ?? null;
        $this->errorMessage = $data['error_message'] ?? null;
    }

    // Getters
    public function getId(): ?int {
        return $this->id;
    }

    public function getInvoiceId(): int {
        return $this->invoiceId;
    }

    public function getCondominiumId(): int {
        return $this->condominiumId;
    }

    public function getAmount(): float {
        return $this->amount;
    }

    public function getPaymentMethod(): string {
        return $this->paymentMethod;
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function getTransactionId(): ?string {
        return $this->transactionId;
    }

    public function getGatewayReference(): ?string {
        return $this->gatewayReference;
    }

    public function getPaymentDate(): DateTime {
        return $this->paymentDate;
    }

    public function getReceiptUrl(): ?string {
        return $this->receiptUrl;
    }

    public function getProcessingFee(): ?float {
        return $this->processingFee;
    }

    public function getErrorMessage(): ?string {
        return $this->errorMessage;
    }

    // Setters
    public function setId(?int $id): void {
        $this->id = $id;
    }

    public function setStatus(string $status): void {
        $this->status = $status;
    }

    public function setTransactionId(?string $transactionId): void {
        $this->transactionId = $transactionId;
    }

    public function setGatewayReference(?string $gatewayReference): void {
        $this->gatewayReference = $gatewayReference;
    }

    public function setReceiptUrl(?string $receiptUrl): void {
        $this->receiptUrl = $receiptUrl;
    }

    public function setProcessingFee(?float $processingFee): void {
        $this->processingFee = $processingFee;
    }

    public function setErrorMessage(?string $errorMessage): void {
        $this->errorMessage = $errorMessage;
    }

    /**
     * Verifica se o pagamento foi bem-sucedido
     * @return bool Status de sucesso do pagamento
     */
    public function isSuccessful(): bool {
        return $this->status === 'completed' || $this->status === 'paid';
    }

    /**
     * Verifica se o pagamento está pendente
     * @return bool Status pendente do pagamento
     */
    public function isPending(): bool {
        return $this->status === 'pending' || $this->status === 'processing';
    }

    /**
     * Verifica se o pagamento falhou
     * @return bool Status de falha do pagamento
     */
    public function isFailed(): bool {
        return $this->status === 'failed' || $this->status === 'canceled';
    }
}
