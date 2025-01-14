<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\FinancialChartInterface;
use CondMan\Domain\Repositories\FinancialTransactionRepositoryInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use DateTime;
use Exception;

class FinancialChartService implements FinancialChartInterface {
    private FinancialTransactionRepositoryInterface $transactionRepository;
    private LoggerInterface $logger;

    public function __construct(
        FinancialTransactionRepositoryInterface $transactionRepository,
        LoggerInterface $logger
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->logger = $logger;
    }

    public function generateRevenueCategoryChart(int $condominiumId, DateTime $startDate, DateTime $endDate): array {
        try {
            $filters = [
                'condominium_id' => $condominiumId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => 'revenue'
            ];

            $transactions = $this->transactionRepository->findByFilters($filters);

            $categoryData = [];
            foreach ($transactions as $transaction) {
                $category = $transaction->getCategory();
                $categoryData[$category] = 
                    ($categoryData[$category] ?? 0) + $transaction->getAmount();
            }

            $chart = [
                'type' => 'pie',
                'labels' => array_keys($categoryData),
                'datasets' => [
                    'data' => array_values($categoryData)
                ]
            ];

            $this->logger->info('Gráfico de receitas por categoria gerado', [
                'condominium_id' => $condominiumId,
                'categories_count' => count($categoryData)
            ]);

            return $chart;
        } catch (Exception $e) {
            $this->logger->error('Erro ao gerar gráfico de receitas', [
                'error' => $e->getMessage(),
                'condominium_id' => $condominiumId
            ]);
            throw $e;
        }
    }

    public function generateExpenseCategoryChart(int $condominiumId, DateTime $startDate, DateTime $endDate): array {
        try {
            $filters = [
                'condominium_id' => $condominiumId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => 'expense'
            ];

            $transactions = $this->transactionRepository->findByFilters($filters);

            $categoryData = [];
            foreach ($transactions as $transaction) {
                $category = $transaction->getCategory();
                $categoryData[$category] = 
                    ($categoryData[$category] ?? 0) + $transaction->getAmount();
            }

            $chart = [
                'type' => 'pie',
                'labels' => array_keys($categoryData),
                'datasets' => [
                    'data' => array_values($categoryData)
                ]
            ];

            $this->logger->info('Gráfico de despesas por categoria gerado', [
                'condominium_id' => $condominiumId,
                'categories_count' => count($categoryData)
            ]);

            return $chart;
        } catch (Exception $e) {
            $this->logger->error('Erro ao gerar gráfico de despesas', [
                'error' => $e->getMessage(),
                'condominium_id' => $condominiumId
            ]);
            throw $e;
        }
    }

    public function generateCashFlowChart(int $condominiumId, DateTime $startDate, DateTime $endDate): array {
        try {
            $filters = [
                'condominium_id' => $condominiumId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ];

            $transactions = $this->transactionRepository->findByFilters($filters);

            $dailyFlow = [];
            foreach ($transactions as $transaction) {
                $date = $transaction->getDate()->format('Y-m-d');
                $amount = $transaction->getType() === 'revenue' 
                    ? $transaction->getAmount() 
                    : -$transaction->getAmount();

                $dailyFlow[$date] = ($dailyFlow[$date] ?? 0) + $amount;
            }

            ksort($dailyFlow);

            $chart = [
                'type' => 'line',
                'labels' => array_keys($dailyFlow),
                'datasets' => [
                    'data' => array_values($dailyFlow)
                ]
            ];

            $this->logger->info('Gráfico de fluxo de caixa gerado', [
                'condominium_id' => $condominiumId,
                'days_count' => count($dailyFlow)
            ]);

            return $chart;
        } catch (Exception $e) {
            $this->logger->error('Erro ao gerar gráfico de fluxo de caixa', [
                'error' => $e->getMessage(),
                'condominium_id' => $condominiumId
            ]);
            throw $e;
        }
    }

    public function generateDelinquencyChart(int $condominiumId, DateTime $referenceDate): array {
        try {
            $filters = [
                'condominium_id' => $condominiumId,
                'status' => 'overdue'
            ];

            $transactions = $this->transactionRepository->findByFilters($filters);

            $delinquencyByCategory = [];
            foreach ($transactions as $transaction) {
                $category = $transaction->getCategory();
                $delinquencyByCategory[$category] = 
                    ($delinquencyByCategory[$category] ?? 0) + $transaction->getAmount();
            }

            $chart = [
                'type' => 'bar',
                'labels' => array_keys($delinquencyByCategory),
                'datasets' => [
                    'data' => array_values($delinquencyByCategory)
                ]
            ];

            $this->logger->info('Gráfico de inadimplência gerado', [
                'condominium_id' => $condominiumId,
                'categories_count' => count($delinquencyByCategory)
            ]);

            return $chart;
        } catch (Exception $e) {
            $this->logger->error('Erro ao gerar gráfico de inadimplência', [
                'error' => $e->getMessage(),
                'condominium_id' => $condominiumId
            ]);
            throw $e;
        }
    }

    public function generateRevenueExpenseComparisonChart(int $condominiumId, DateTime $startDate, DateTime $endDate): array {
        try {
            $revenueFilters = [
                'condominium_id' => $condominiumId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => 'revenue'
            ];

            $expenseFilters = [
                'condominium_id' => $condominiumId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => 'expense'
            ];

            $revenueTransactions = $this->transactionRepository->findByFilters($revenueFilters);
            $expenseTransactions = $this->transactionRepository->findByFilters($expenseFilters);

            $monthlyRevenue = [];
            $monthlyExpenses = [];

            foreach ($revenueTransactions as $transaction) {
                $month = $transaction->getDate()->format('Y-m');
                $monthlyRevenue[$month] = 
                    ($monthlyRevenue[$month] ?? 0) + $transaction->getAmount();
            }

            foreach ($expenseTransactions as $transaction) {
                $month = $transaction->getDate()->format('Y-m');
                $monthlyExpenses[$month] = 
                    ($monthlyExpenses[$month] ?? 0) + $transaction->getAmount();
            }

            $chart = [
                'type' => 'bar',
                'labels' => array_keys($monthlyRevenue),
                'datasets' => [
                    'revenue' => array_values($monthlyRevenue),
                    'expenses' => array_values($monthlyExpenses)
                ]
            ];

            $this->logger->info('Gráfico comparativo de receitas e despesas gerado', [
                'condominium_id' => $condominiumId,
                'months_count' => count($monthlyRevenue)
            ]);

            return $chart;
        } catch (Exception $e) {
            $this->logger->error('Erro ao gerar gráfico comparativo', [
                'error' => $e->getMessage(),
                'condominium_id' => $condominiumId
            ]);
            throw $e;
        }
    }
}
