<?php
namespace CondMan\Domain\Interfaces;

use DateTime;

interface FinancialChartInterface {
    /**
     * Gera gráfico de receitas por categoria
     */
    public function generateRevenueCategoryChart(int $condominiumId, DateTime $startDate, DateTime $endDate): array;

    /**
     * Gera gráfico de despesas por categoria
     */
    public function generateExpenseCategoryChart(int $condominiumId, DateTime $startDate, DateTime $endDate): array;

    /**
     * Gera gráfico de fluxo de caixa
     */
    public function generateCashFlowChart(int $condominiumId, DateTime $startDate, DateTime $endDate): array;

    /**
     * Gera gráfico de inadimplência
     */
    public function generateDelinquencyChart(int $condominiumId, DateTime $referenceDate): array;

    /**
     * Gera comparativo de receitas e despesas
     */
    public function generateRevenueExpenseComparisonChart(int $condominiumId, DateTime $startDate, DateTime $endDate): array;
}
