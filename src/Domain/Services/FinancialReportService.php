<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\FinancialReportInterface;
use CondMan\Domain\Repositories\FinancialTransactionRepositoryInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use DateTime;
use Exception;

class FinancialReportService implements FinancialReportInterface {
    private FinancialTransactionRepositoryInterface $transactionRepository;
    private LoggerInterface $logger;

    public function __construct(
        FinancialTransactionRepositoryInterface $transactionRepository,
        LoggerInterface $logger
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->logger = $logger;
    }

    public function generateRevenueReport(int $condominiumId, DateTime $startDate, DateTime $endDate): array {
        try {
            $filters = [
                'condominium_id' => $condominiumId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => 'revenue'
            ];

            $transactions = $this->transactionRepository->findByFilters($filters);

            $report = [
                'total_revenue' => 0,
                'transactions' => [],
                'categories' => []
            ];

            foreach ($transactions as $transaction) {
                $report['total_revenue'] += $transaction->getAmount();
                $report['transactions'][] = $transaction->jsonSerialize();
                
                $category = $transaction->getCategory();
                $report['categories'][$category] = 
                    ($report['categories'][$category] ?? 0) + $transaction->getAmount();
            }

            $this->logger->info('Relatório de receitas gerado', [
                'condominium_id' => $condominiumId,
                'total_revenue' => $report['total_revenue']
            ]);

            return $report;
        } catch (Exception $e) {
            $this->logger->error('Erro ao gerar relatório de receitas', [
                'error' => $e->getMessage(),
                'condominium_id' => $condominiumId
            ]);
            throw $e;
        }
    }

    public function generateExpenseReport(int $condominiumId, DateTime $startDate, DateTime $endDate): array {
        try {
            $filters = [
                'condominium_id' => $condominiumId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => 'expense'
            ];

            $transactions = $this->transactionRepository->findByFilters($filters);

            $report = [
                'total_expenses' => 0,
                'transactions' => [],
                'categories' => []
            ];

            foreach ($transactions as $transaction) {
                $report['total_expenses'] += $transaction->getAmount();
                $report['transactions'][] = $transaction->jsonSerialize();
                
                $category = $transaction->getCategory();
                $report['categories'][$category] = 
                    ($report['categories'][$category] ?? 0) + $transaction->getAmount();
            }

            $this->logger->info('Relatório de despesas gerado', [
                'condominium_id' => $condominiumId,
                'total_expenses' => $report['total_expenses']
            ]);

            return $report;
        } catch (Exception $e) {
            $this->logger->error('Erro ao gerar relatório de despesas', [
                'error' => $e->getMessage(),
                'condominium_id' => $condominiumId
            ]);
            throw $e;
        }
    }

    public function generateDelinquencyReport(int $condominiumId, DateTime $referenceDate): array {
        try {
            // Simulação de relatório de inadimplência
            // Em produção, integraria com serviço de faturas/pagamentos
            $filters = [
                'condominium_id' => $condominiumId,
                'status' => 'overdue'
            ];

            $transactions = $this->transactionRepository->findByFilters($filters);

            $report = [
                'total_delinquent_amount' => 0,
                'delinquent_transactions' => [],
                'delinquency_rate' => 0
            ];

            foreach ($transactions as $transaction) {
                $report['total_delinquent_amount'] += $transaction->getAmount();
                $report['delinquent_transactions'][] = $transaction->jsonSerialize();
            }

            // Cálculo de taxa de inadimplência (simulado)
            $totalTransactions = $this->transactionRepository->countByFilters([
                'condominium_id' => $condominiumId
            ]);

            $report['delinquency_rate'] = 
                $totalTransactions > 0 
                    ? ($report['total_delinquent_amount'] / $totalTransactions) * 100 
                    : 0;

            $this->logger->info('Relatório de inadimplência gerado', [
                'condominium_id' => $condominiumId,
                'total_delinquent_amount' => $report['total_delinquent_amount'],
                'delinquency_rate' => $report['delinquency_rate']
            ]);

            return $report;
        } catch (Exception $e) {
            $this->logger->error('Erro ao gerar relatório de inadimplência', [
                'error' => $e->getMessage(),
                'condominium_id' => $condominiumId
            ]);
            throw $e;
        }
    }

    public function generateConsolidatedReport(int $condominiumId, DateTime $startDate, DateTime $endDate): array {
        try {
            $revenueReport = $this->generateRevenueReport($condominiumId, $startDate, $endDate);
            $expenseReport = $this->generateExpenseReport($condominiumId, $startDate, $endDate);

            $report = [
                'period_start' => $startDate->format('Y-m-d'),
                'period_end' => $endDate->format('Y-m-d'),
                'total_revenue' => $revenueReport['total_revenue'],
                'total_expenses' => $expenseReport['total_expenses'],
                'net_balance' => $revenueReport['total_revenue'] - $expenseReport['total_expenses'],
                'revenue_categories' => $revenueReport['categories'],
                'expense_categories' => $expenseReport['categories']
            ];

            $this->logger->info('Relatório consolidado gerado', [
                'condominium_id' => $condominiumId,
                'net_balance' => $report['net_balance']
            ]);

            return $report;
        } catch (Exception $e) {
            $this->logger->error('Erro ao gerar relatório consolidado', [
                'error' => $e->getMessage(),
                'condominium_id' => $condominiumId
            ]);
            throw $e;
        }
    }

    public function exportReport(array $reportData, string $format = 'csv'): string {
        try {
            // Implementação básica de exportação
            // Em produção, usar biblioteca de exportação mais robusta
            switch ($format) {
                case 'csv':
                    $output = $this->exportToCsv($reportData);
                    break;
                case 'json':
                    $output = json_encode($reportData);
                    break;
                default:
                    throw new Exception("Formato de exportação não suportado: {$format}");
            }

            $this->logger->info('Relatório exportado', [
                'format' => $format,
                'data_size' => strlen($output)
            ]);

            return $output;
        } catch (Exception $e) {
            $this->logger->error('Erro ao exportar relatório', [
                'error' => $e->getMessage(),
                'format' => $format
            ]);
            throw $e;
        }
    }

    private function exportToCsv(array $reportData): string {
        $csv = [];
        
        // Cabeçalho
        $csv[] = implode(',', array_keys($reportData));
        
        // Dados
        $row = [];
        foreach ($reportData as $value) {
            $row[] = is_array($value) ? json_encode($value) : $value;
        }
        $csv[] = implode(',', $row);

        return implode("\n", $csv);
    }
}
