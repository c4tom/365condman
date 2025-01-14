<?php
namespace CondMan\Domain\Interfaces;

interface FinancialInterface {
    /**
     * Calcula valor total de uma entidade financeira
     * @return float Valor total
     */
    public function calculateTotal(): float;

    /**
     * Verifica se a entidade financeira está paga
     * @return bool Status de pagamento
     */
    public function isPaid(): bool;

    /**
     * Obtém status financeiro
     * @return string Status financeiro
     */
    public function getFinancialStatus(): string;

    /**
     * Aplica desconto
     * @param float $discountPercentage Percentual de desconto
     * @return float Valor com desconto
     */
    public function applyDiscount(float $discountPercentage): float;

    /**
     * Aplica multa por atraso
     * @param float $penaltyPercentage Percentual de multa
     * @return float Valor com multa
     */
    public function applyLatePenalty(float $penaltyPercentage): float;
}
