<?php
namespace CondMan\Tests\Integration\Services;

use PHPUnit\Framework\TestCase;
use CondMan\Domain\Services\FinancialReportService;
use CondMan\Domain\Services\FinancialChartService;
use CondMan\Infrastructure\Repositories\WordPressFinancialTransactionRepository;
use CondMan\Domain\Entities\FinancialTransaction;
use CondMan\Domain\Interfaces\LoggerInterface;
use DateTime;
use wpdb;

class FinancialIntegrationTest extends TestCase {
    private WordPressFinancialTransactionRepository $repository;
    private FinancialReportService $reportService;
    private FinancialChartService $chartService;
    private LoggerInterface $logger;
    private wpdb $wpdb;
    private string $testTableName;

    protected function setUp(): void {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Criar logger mock
        $this->logger = $this->createMock(LoggerInterface::class);
        
        // Nome da tabela de teste
        $this->testTableName = $this->wpdb->prefix . 'financial_transactions_integration_test';
        
        // Criar tabela de teste
        $this->createTestTable();
        
        // Inicializar repositório
        $this->repository = new WordPressFinancialTransactionRepository(
            $this->wpdb, 
            $this->logger, 
            $this->testTableName
        );

        // Inicializar serviços
        $this->reportService = new FinancialReportService(
            $this->repository,
            $this->logger
        );

        $this->chartService = new FinancialChartService(
            $this->repository,
            $this->logger
        );
    }

    protected function tearDown(): void {
        // Limpar tabela de teste
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->testTableName}");
    }

    private function createTestTable(): void {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->testTableName} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            condominium_id BIGINT(20) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            type ENUM('revenue', 'expense') NOT NULL,
            category VARCHAR(100) NOT NULL,
            description TEXT,
            date DATETIME NOT NULL,
            status ENUM('pending', 'completed', 'cancelled', 'overdue') NOT NULL,
            invoice_id BIGINT(20),
            payment_id BIGINT(20),
            reference VARCHAR(255),
            metadata JSON,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private function createTestTransactions(int $condominiumId): void {
        $transactions = [
            new FinancialTransaction(
                null, $condominiumId, 1000.00, 'revenue', 'aluguel', 
                'Aluguel Jan', new DateTime('2024-01-15'), 'completed'
            ),
            new FinancialTransaction(
                null, $condominiumId, 500.00, 'expense', 'manutencao', 
                'Manutenção', new DateTime('2024-01-20'), 'completed'
            ),
            new FinancialTransaction(
                null, $condominiumId, 800.00, 'revenue', 'taxa_condominio', 
                'Taxa Cond', new DateTime('2024-01-25'), 'pending'
            ),
            new FinancialTransaction(
                null, $condominiumId, 200.00, 'expense', 'limpeza', 
                'Limpeza', new DateTime('2024-01-10'), 'overdue'
            )
        ];

        array_map(fn($t) => $this->repository->save($t), $transactions);
    }

    public function testFinancialReportIntegration(): void {
        $condominiumId = 1;
        $startDate = new DateTime('2024-01-01');
        $endDate = new DateTime('2024-01-31');

        // Criar transações de teste
        $this->createTestTransactions($condominiumId);

        // Gerar relatórios
        $revenueReport = $this->reportService->generateRevenueReport($condominiumId, $startDate, $endDate);
        $expenseReport = $this->reportService->generateExpenseReport($condominiumId, $startDate, $endDate);
        $delinquencyReport = $this->reportService->generateDelinquencyReport($condominiumId, $endDate);
        $consolidatedReport = $this->reportService->generateConsolidatedReport($condominiumId, $startDate, $endDate);

        // Verificações de Relatório de Receitas
        $this->assertArrayHasKey('total_revenue', $revenueReport);
        $this->assertEquals(1800.00, $revenueReport['total_revenue']);
        $this->assertCount(2, $revenueReport['transactions']);
        $this->assertArrayHasKey('aluguel', $revenueReport['categories']);
        $this->assertArrayHasKey('taxa_condominio', $revenueReport['categories']);

        // Verificações de Relatório de Despesas
        $this->assertArrayHasKey('total_expenses', $expenseReport);
        $this->assertEquals(700.00, $expenseReport['total_expenses']);
        $this->assertCount(2, $expenseReport['transactions']);
        $this->assertArrayHasKey('manutencao', $expenseReport['categories']);
        $this->assertArrayHasKey('limpeza', $expenseReport['categories']);

        // Verificações de Relatório de Inadimplência
        $this->assertArrayHasKey('total_delinquent_amount', $delinquencyReport);
        $this->assertEquals(200.00, $delinquencyReport['total_delinquent_amount']);
        $this->assertCount(1, $delinquencyReport['delinquent_transactions']);

        // Verificações de Relatório Consolidado
        $this->assertArrayHasKey('total_revenue', $consolidatedReport);
        $this->assertArrayHasKey('total_expenses', $consolidatedReport);
        $this->assertArrayHasKey('net_balance', $consolidatedReport);
        $this->assertEquals(1800.00, $consolidatedReport['total_revenue']);
        $this->assertEquals(700.00, $consolidatedReport['total_expenses']);
        $this->assertEquals(1100.00, $consolidatedReport['net_balance']);
    }

    public function testFinancialChartIntegration(): void {
        $condominiumId = 1;
        $startDate = new DateTime('2024-01-01');
        $endDate = new DateTime('2024-01-31');

        // Criar transações de teste
        $this->createTestTransactions($condominiumId);

        // Gerar gráficos
        $revenueCategoryChart = $this->chartService->generateRevenueCategoryChart($condominiumId, $startDate, $endDate);
        $expenseCategoryChart = $this->chartService->generateExpenseCategoryChart($condominiumId, $startDate, $endDate);
        $cashFlowChart = $this->chartService->generateCashFlowChart($condominiumId, $startDate, $endDate);
        $delinquencyChart = $this->chartService->generateDelinquencyChart($condominiumId, $endDate);
        $revenueExpenseComparisonChart = $this->chartService->generateRevenueExpenseComparisonChart($condominiumId, $startDate, $endDate);

        // Verificações de Gráfico de Receitas por Categoria
        $this->assertEquals('pie', $revenueCategoryChart['type']);
        $this->assertCount(2, $revenueCategoryChart['labels']);
        $this->assertContains('aluguel', $revenueCategoryChart['labels']);
        $this->assertContains('taxa_condominio', $revenueCategoryChart['labels']);
        $this->assertEquals([1000.00, 800.00], $revenueCategoryChart['datasets']['data']);

        // Verificações de Gráfico de Despesas por Categoria
        $this->assertEquals('pie', $expenseCategoryChart['type']);
        $this->assertCount(2, $expenseCategoryChart['labels']);
        $this->assertContains('manutencao', $expenseCategoryChart['labels']);
        $this->assertContains('limpeza', $expenseCategoryChart['labels']);
        $this->assertEquals([500.00, 200.00], $expenseCategoryChart['datasets']['data']);

        // Verificações de Gráfico de Fluxo de Caixa
        $this->assertEquals('line', $cashFlowChart['type']);
        $this->assertCount(4, $cashFlowChart['labels']);
        $this->assertContains('2024-01-10', $cashFlowChart['labels']);
        $this->assertContains('2024-01-15', $cashFlowChart['labels']);
        $this->assertContains('2024-01-20', $cashFlowChart['labels']);
        $this->assertContains('2024-01-25', $cashFlowChart['labels']);

        // Verificações de Gráfico de Inadimplência
        $this->assertEquals('bar', $delinquencyChart['type']);
        $this->assertCount(1, $delinquencyChart['labels']);
        $this->assertContains('limpeza', $delinquencyChart['labels']);
        $this->assertEquals([200.00], $delinquencyChart['datasets']['data']);

        // Verificações de Gráfico Comparativo de Receitas e Despesas
        $this->assertEquals('bar', $revenueExpenseComparisonChart['type']);
        $this->assertCount(1, $revenueExpenseComparisonChart['labels']);
        $this->assertContains('2024-01', $revenueExpenseComparisonChart['labels']);
        $this->assertEquals([1800.00], $revenueExpenseComparisonChart['datasets']['revenue']);
        $this->assertEquals([700.00], $revenueExpenseComparisonChart['datasets']['expenses']);
    }
}
