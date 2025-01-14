<?php
namespace CondMan\Domain\Interfaces;

use DateTime;

interface FinancialReportInterface {
    /**
     * Gera relatório de receitas
     */
    public function generateRevenueReport(int $condominiumId, DateTime $startDate, DateTime $endDate): array;

    /**
     * Gera relatório de despesas
     */
    public function generateExpenseReport(int $condominiumId, DateTime $startDate, DateTime $endDate): array;

    /**
     * Gera relatório de inadimplência
     */
    public function generateDelinquencyReport(int $condominiumId, DateTime $referenceDate): array;

    /**
     * Gera relatório consolidado
     */
    public function generateConsolidatedReport(int $condominiumId, DateTime $startDate, DateTime $endDate): array;

    /**
     * Exporta relatório para formato específico
     */
    public function exportReport(array $reportData, string $format): string;
}
