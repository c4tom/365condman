<?php
namespace CondMan\Domain\Interfaces;

interface ExpenseInterface {
    /**
     * Obtém a categoria da despesa
     * @return string Categoria da despesa
     */
    public function getCategory(): string;

    /**
     * Define a categoria da despesa
     * @param string $category Categoria da despesa
     */
    public function setCategory(string $category): void;

    /**
     * Obtém o fornecedor da despesa
     * @return string Nome do fornecedor
     */
    public function getSupplier(): string;

    /**
     * Define o fornecedor da despesa
     * @param string $supplier Nome do fornecedor
     */
    public function setSupplier(string $supplier): void;

    /**
     * Calcula o valor total da despesa, incluindo impostos e taxas
     * @return float Valor total da despesa
     */
    public function calculateTotalAmount(): float;

    /**
     * Verifica se a despesa está aprovada
     * @return bool Status de aprovação
     */
    public function isApproved(): bool;

    /**
     * Obtém o status de pagamento da despesa
     * @return string Status de pagamento
     */
    public function getPaymentStatus(): string;

    /**
     * Aplica desconto na despesa
     * @param float $discountPercentage Percentual de desconto
     * @return float Valor com desconto
     */
    public function applyDiscount(float $discountPercentage): float;

    /**
     * Verifica se a despesa é recorrente
     * @return bool Indica se é uma despesa recorrente
     */
    public function isRecurring(): bool;

    /**
     * Obtém a frequência de recorrência
     * @return string Frequência de recorrência (mensal, trimestral, anual)
     */
    public function getRecurrenceFrequency(): string;
}
