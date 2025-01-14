<?php
namespace CondMan\Domain\Transformers;

use CondMan\Domain\Interfaces\InvoiceInterface;
use DateTime;
use NumberFormatter;

class InvoiceTransformer {
    private InvoiceInterface $invoice;
    private NumberFormatter $currencyFormatter;
    private NumberFormatter $numberFormatter;

    public function __construct(InvoiceInterface $invoice) {
        $this->invoice = $invoice;
        $this->currencyFormatter = new NumberFormatter('pt_BR', NumberFormatter::CURRENCY);
        $this->numberFormatter = new NumberFormatter('pt_BR', NumberFormatter::DECIMAL);
    }

    /**
     * Formata o valor total da fatura
     * @return string Valor formatado
     */
    public function formatTotalAmount(): string {
        return $this->currencyFormatter->formatCurrency(
            $this->invoice->getTotalAmount(), 
            'BRL'
        );
    }

    /**
     * Formata o valor pago
     * @return string Valor pago formatado
     */
    public function formatTotalPaid(): string {
        return $this->currencyFormatter->formatCurrency(
            $this->invoice->getTotalPaid(), 
            'BRL'
        );
    }

    /**
     * Calcula o valor restante a pagar
     * @return float Valor restante
     */
    public function calculateRemainingAmount(): float {
        return max(
            0, 
            $this->invoice->getTotalAmount() - $this->invoice->getTotalPaid()
        );
    }

    /**
     * Gera referência da fatura
     * @return string Referência
     */
    public function generateReference(): string {
        return sprintf(
            "%s/%s", 
            $this->invoice->getReferenceMonth(), 
            $this->invoice->getReferenceYear()
        );
    }

    /**
     * Traduz o status da fatura
     * @return string Status traduzido
     */
    public function translateStatus(): string {
        $translations = [
            'pending' => 'Pendente',
            'paid' => 'Pago',
            'overdue' => 'Vencido',
            'partial' => 'Parcial',
            'canceled' => 'Cancelado'
        ];
        
        return $translations[$this->invoice->getStatus()] ?? $this->invoice->getStatus();
    }

    /**
     * Verifica se a fatura está vencida
     * @return bool Indica se a fatura está vencida
     */
    public function isOverdue(): bool {
        $dueDate = $this->invoice->getDueDate();
        $now = new DateTime();
        
        return $dueDate < $now && 
               $this->invoice->getTotalPaid() < $this->invoice->getTotalAmount();
    }

    /**
     * Calcula dias de atraso
     * @return int Dias de atraso
     */
    public function calculateOverdueDays(): int {
        if (!$this->isOverdue()) {
            return 0;
        }
        
        $dueDate = $this->invoice->getDueDate();
        $now = new DateTime();
        
        return $now->diff($dueDate)->days;
    }

    /**
     * Gera um resumo da fatura
     * @return array Resumo da fatura
     */
    public function generateSummary(): array {
        return [
            'reference' => $this->generateReference(),
            'total_amount' => $this->formatTotalAmount(),
            'total_paid' => $this->formatTotalPaid(),
            'remaining_amount' => $this->currencyFormatter->formatCurrency(
                $this->calculateRemainingAmount(), 
                'BRL'
            ),
            'status' => $this->translateStatus(),
            'is_overdue' => $this->isOverdue(),
            'overdue_days' => $this->calculateOverdueDays()
        ];
    }
}
