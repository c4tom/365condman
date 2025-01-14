<?php
namespace CondMan\Tests\Domain\Services;

use PHPUnit\Framework\TestCase;
use CondMan\Domain\Services\FinancialChartService;
use CondMan\Domain\Repositories\FinancialTransactionRepositoryInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use CondMan\Domain\Entities\FinancialTransaction;
use DateTime;
use Mockery;

class FinancialChartServiceTest extends TestCase {
    private FinancialChartService $chartService;
    private FinancialTransactionRepositoryInterface $mockRepository;
    private LoggerInterface $mockLogger;

    protected function setUp(): void {
        $this->mockRepository = Mockery::mock(FinancialTransactionRepositoryInterface::class);
        $this->mockLogger = Mockery::mock(LoggerInterface::class);

        $this->chartService = new FinancialChartService(
            $this->mockRepository,
            $this->mockLogger
        );
    }

    protected function tearDown(): void {
        Mockery::close();
    }

    public function testGenerateRevenueCategoryChart() {
        $condominiumId = 1;
        $startDate = new DateTime('2024-01-01');
        $endDate = new DateTime('2024-01-31');

        $mockTransaction1 = Mockery::mock(FinancialTransaction::class);
        $mockTransaction1->shouldReceive('getAmount')->andReturn(1000.00);
        $mockTransaction1->shouldReceive('getCategory')->andReturn('aluguel');
        $mockTransaction1->shouldReceive('getDate')->andReturn($startDate);

        $mockTransaction2 = Mockery::mock(FinancialTransaction::class);
        $mockTransaction2->shouldReceive('getAmount')->andReturn(500.00);
        $mockTransaction2->shouldReceive('getCategory')->andReturn('taxa_condominio');
        $mockTransaction2->shouldReceive('getDate')->andReturn($startDate);

        $this->mockRepository
            ->shouldReceive('findByFilters')
            ->with([
                'condominium_id' => $condominiumId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => 'revenue'
            ])
            ->andReturn([$mockTransaction1, $mockTransaction2]);

        $this->mockLogger
            ->shouldReceive('info')
            ->with('Gráfico de receitas por categoria gerado', Mockery::any());

        $chart = $this->chartService->generateRevenueCategoryChart($condominiumId, $startDate, $endDate);

        $this->assertEquals('pie', $chart['type']);
        $this->assertCount(2, $chart['labels']);
        $this->assertCount(1, $chart['datasets']);
        $this->assertContains('aluguel', $chart['labels']);
        $this->assertContains('taxa_condominio', $chart['labels']);
        $this->assertEquals([1000.00, 500.00], $chart['datasets']['data']);
    }

    public function testGenerateExpenseCategoryChart() {
        $condominiumId = 1;
        $startDate = new DateTime('2024-01-01');
        $endDate = new DateTime('2024-01-31');

        $mockTransaction1 = Mockery::mock(FinancialTransaction::class);
        $mockTransaction1->shouldReceive('getAmount')->andReturn(800.00);
        $mockTransaction1->shouldReceive('getCategory')->andReturn('manutencao');
        $mockTransaction1->shouldReceive('getDate')->andReturn($startDate);

        $mockTransaction2 = Mockery::mock(FinancialTransaction::class);
        $mockTransaction2->shouldReceive('getAmount')->andReturn(300.00);
        $mockTransaction2->shouldReceive('getCategory')->andReturn('limpeza');
        $mockTransaction2->shouldReceive('getDate')->andReturn($startDate);

        $this->mockRepository
            ->shouldReceive('findByFilters')
            ->with([
                'condominium_id' => $condominiumId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => 'expense'
            ])
            ->andReturn([$mockTransaction1, $mockTransaction2]);

        $this->mockLogger
            ->shouldReceive('info')
            ->with('Gráfico de despesas por categoria gerado', Mockery::any());

        $chart = $this->chartService->generateExpenseCategoryChart($condominiumId, $startDate, $endDate);

        $this->assertEquals('pie', $chart['type']);
        $this->assertCount(2, $chart['labels']);
        $this->assertCount(1, $chart['datasets']);
        $this->assertContains('manutencao', $chart['labels']);
        $this->assertContains('limpeza', $chart['labels']);
        $this->assertEquals([800.00, 300.00], $chart['datasets']['data']);
    }

    public function testGenerateCashFlowChart() {
        $condominiumId = 1;
        $startDate = new DateTime('2024-01-01');
        $endDate = new DateTime('2024-01-31');

        $mockTransaction1 = Mockery::mock(FinancialTransaction::class);
        $mockTransaction1->shouldReceive('getAmount')->andReturn(1000.00);
        $mockTransaction1->shouldReceive('getType')->andReturn('revenue');
        $mockTransaction1->shouldReceive('getDate')->andReturn(new DateTime('2024-01-15'));

        $mockTransaction2 = Mockery::mock(FinancialTransaction::class);
        $mockTransaction2->shouldReceive('getAmount')->andReturn(500.00);
        $mockTransaction2->shouldReceive('getType')->andReturn('expense');
        $mockTransaction2->shouldReceive('getDate')->andReturn(new DateTime('2024-01-20'));

        $this->mockRepository
            ->shouldReceive('findByFilters')
            ->with([
                'condominium_id' => $condominiumId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ])
            ->andReturn([$mockTransaction1, $mockTransaction2]);

        $this->mockLogger
            ->shouldReceive('info')
            ->with('Gráfico de fluxo de caixa gerado', Mockery::any());

        $chart = $this->chartService->generateCashFlowChart($condominiumId, $startDate, $endDate);

        $this->assertEquals('line', $chart['type']);
        $this->assertCount(2, $chart['labels']);
        $this->assertCount(1, $chart['datasets']);
        $this->assertContains('2024-01-15', $chart['labels']);
        $this->assertContains('2024-01-20', $chart['labels']);
        $this->assertEquals([1000.00, -500.00], $chart['datasets']['data']);
    }

    public function testGenerateDelinquencyChart() {
        $condominiumId = 1;
        $referenceDate = new DateTime('2024-01-31');

        $mockTransaction1 = Mockery::mock(FinancialTransaction::class);
        $mockTransaction1->shouldReceive('getAmount')->andReturn(500.00);
        $mockTransaction1->shouldReceive('getCategory')->andReturn('aluguel');

        $mockTransaction2 = Mockery::mock(FinancialTransaction::class);
        $mockTransaction2->shouldReceive('getAmount')->andReturn(200.00);
        $mockTransaction2->shouldReceive('getCategory')->andReturn('taxa_condominio');

        $this->mockRepository
            ->shouldReceive('findByFilters')
            ->with([
                'condominium_id' => $condominiumId,
                'status' => 'overdue'
            ])
            ->andReturn([$mockTransaction1, $mockTransaction2]);

        $this->mockLogger
            ->shouldReceive('info')
            ->with('Gráfico de inadimplência gerado', Mockery::any());

        $chart = $this->chartService->generateDelinquencyChart($condominiumId, $referenceDate);

        $this->assertEquals('bar', $chart['type']);
        $this->assertCount(2, $chart['labels']);
        $this->assertCount(1, $chart['datasets']);
        $this->assertContains('aluguel', $chart['labels']);
        $this->assertContains('taxa_condominio', $chart['labels']);
        $this->assertEquals([500.00, 200.00], $chart['datasets']['data']);
    }

    public function testGenerateRevenueExpenseComparisonChart() {
        $condominiumId = 1;
        $startDate = new DateTime('2024-01-01');
        $endDate = new DateTime('2024-01-31');

        $mockRevenueTransaction1 = Mockery::mock(FinancialTransaction::class);
        $mockRevenueTransaction1->shouldReceive('getAmount')->andReturn(1500.00);
        $mockRevenueTransaction1->shouldReceive('getDate')->andReturn(new DateTime('2024-01'));

        $mockExpenseTransaction1 = Mockery::mock(FinancialTransaction::class);
        $mockExpenseTransaction1->shouldReceive('getAmount')->andReturn(1000.00);
        $mockExpenseTransaction1->shouldReceive('getDate')->andReturn(new DateTime('2024-01'));

        $this->mockRepository
            ->shouldReceive('findByFilters')
            ->with([
                'condominium_id' => $condominiumId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => 'revenue'
            ])
            ->andReturn([$mockRevenueTransaction1]);

        $this->mockRepository
            ->shouldReceive('findByFilters')
            ->with([
                'condominium_id' => $condominiumId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => 'expense'
            ])
            ->andReturn([$mockExpenseTransaction1]);

        $this->mockLogger
            ->shouldReceive('info')
            ->with('Gráfico comparativo de receitas e despesas gerado', Mockery::any());

        $chart = $this->chartService->generateRevenueExpenseComparisonChart($condominiumId, $startDate, $endDate);

        $this->assertEquals('bar', $chart['type']);
        $this->assertCount(1, $chart['labels']);
        $this->assertCount(2, $chart['datasets']);
        $this->assertContains('2024-01', $chart['labels']);
        $this->assertEquals([1500.00], $chart['datasets']['revenue']);
        $this->assertEquals([1000.00], $chart['datasets']['expenses']);
    }
}
