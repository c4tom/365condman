<?php
namespace CondMan\Domain\Interfaces;

interface RevenueInterface {
    /**
     * Obtém a fonte da receita
     * @return string Fonte da receita
     */
    public function getSource(): string;

    /**
     * Define a fonte da receita
     * @param string $source Fonte da receita
     */
    public function setSource(string $source): void;

    /**
     * Obtém a categoria da receita
     * @return string Categoria da receita
     */
    public function getCategory(): string;

    /**
     * Define a categoria da receita
     * @param string $category Categoria da receita
     */
    public function setCategory(string $category): void;

    /**
     * Calcula o valor total da receita
     * @return float Valor total da receita
     */
    public function calculateTotalAmount(): float;

    /**
     * Verifica se a receita já foi confirmada
     * @return bool Status de confirmação
     */
    public function isConfirmed(): bool;

    /**
     * Obtém o status de recebimento da receita
     * @return string Status de recebimento
     */
    public function getReceiptStatus(): string;

    /**
     * Aplica desconto na receita
     * @param float $discountPercentage Percentual de desconto
     * @return float Valor com desconto
     */
    public function applyDiscount(float $discountPercentage): float;

    /**
     * Verifica se a receita é recorrente
     * @return bool Indica se é uma receita recorrente
     */
    public function isRecurring(): bool;

    /**
     * Obtém a frequência de recorrência
     * @return string Frequência de recorrência (mensal, trimestral, anual)
     */
    public function getRecurrenceFrequency(): string;

    /**
     * Calcula o valor previsto da receita
     * @return float Valor previsto
     */
    public function calculateProjectedAmount(): float;
}
