<?php
namespace CondMan\Tests\Domain\Services;

use PHPUnit\Framework\TestCase;
use CondMan\Domain\Services\FinancialReportService;
use CondMan\Domain\Repositories\FinancialTransactionRepositoryInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use CondMan\Domain\Entities\FinancialTransaction;
use DateTime;
use Exception;
use Mockery;

class FinancialReportServiceTest extends TestCase {
    private FinancialReportService $reportService;
    private FinancialTransactionRepositoryInterface $mockRepository;
    private LoggerInterface $mockLogger;

    protected function setUp(): void {
        $this->mockRepository = Mockery::mock(FinancialTransactionRepositoryInterface::class);
        $this->mockLogger = Mockery::mock(LoggerInterface::class);

        $this->reportService = new FinancialReportService(
            $this->mockRepository,
            $this->mockLogger
        );
    }

    protected function tearDown(): void {
        Mockery::close();
    }

    public function testGenerateRevenueReport() {
        $condominiumId = 1;
        $startDate = new DateTime('2024-01-01');
        $endDate = new DateTime('2024-01-31');

        $mockTransaction1 = Mockery::mock(FinancialTransaction::class);
        $mockTransaction1->shouldReceive('getAmount')->andReturn(1000.00);
        $mockTransaction1->shouldReceive('getCategory')->andReturn('aluguel');
        $mockTransaction1->shouldReceive('jsonSerialize')->andReturn([
            'amount' => 1000.00,
            'category' => 'aluguel'
        ]);

        $mockTransaction2 = Mockery::mock(FinancialTransaction::class);
        $mockTransaction2->shouldReceive('getAmount')->andReturn(500.00);
        $mockTransaction2->shouldReceive('getCategory')->andReturn('taxa_condominio');
        $mockTransaction2->shouldReceive('jsonSerialize')->andReturn([
            'amount' => 500.00,
            'category' => 'taxa_condominio'
        ]);

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
            ->with('Relatório de receitas gerado', Mockery::any());

        $report = $this->reportService->generateRevenueReport($condominiumId, $startDate, $endDate);

        $this->assertArrayHasKey('total_revenue', $report);
        $this->assertArrayHasKey('transactions', $report);
        $this->assertArrayHasKey('categories', $report);

        $this->assertEquals(1500.00, $report['total_revenue']);
        $this->assertCount(2, $report['transactions']);
        $this->assertCount(2, $report['categories']);
        $this->assertEquals(1000.00, $report['categories']['aluguel']);
        $this->assertEquals(500.00, $report['categories']['taxa_condominio']);
    }

    public function testGenerateExpenseReport() {
        $condominiumId = 1;
        $startDate = new DateTime('2024-01-01');
        $endDate = new DateTime('2024-01-31');

        $mockTransaction1 = Mockery::mock(FinancialTransaction::class);
        $mockTransaction1->shouldReceive('getAmount')->andReturn(800.00);
        $mockTransaction1->shouldReceive('getCategory')->andReturn('manutencao');
        $mockTransaction1->shouldReceive('jsonSerialize')->andReturn([
            'amount' => 800.00,
            'category' => 'manutencao'
        ]);

        $mockTransaction2 = Mockery::mock(FinancialTransaction::class);
        $mockTransaction2->shouldReceive('getAmount')->andReturn(300.00);
        $mockTransaction2->shouldReceive('getCategory')->andReturn('limpeza');
        $mockTransaction2->shouldReceive('jsonSerialize')->andReturn([
            'amount' => 300.00,
            'category' => 'limpeza'
        ]);

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
            ->with('Relatório de despesas gerado', Mockery::any());

        $report = $this->reportService->generateExpenseReport($condominiumId, $startDate, $endDate);

        $this->assertArrayHasKey('total_expenses', $report);
        $this->assertArrayHasKey('transactions', $report);
        $this->assertArrayHasKey('categories', $report);

        $this->assertEquals(1100.00, $report['total_expenses']);
        $this->assertCount(2, $report['transactions']);
        $this->assertCount(2, $report['categories']);
        $this->assertEquals(800.00, $report['categories']['manutencao']);
        $this->assertEquals(300.00, $report['categories']['limpeza']);
    }

    public function testGenerateDelinquencyReport() {
        $condominiumId = 1;
        $referenceDate = new DateTime('2024-01-31');

        $mockTransaction1 = Mockery::mock(FinancialTransaction::class);
        $mockTransaction1->shouldReceive('getAmount')->andReturn(500.00);
        $mockTransaction1->shouldReceive('jsonSerialize')->andReturn([
            'amount' => 500.00
        ]);

        $mockTransaction2 = Mockery::mock(FinancialTransaction::class);
        $mockTransaction2->shouldReceive('getAmount')->andReturn(200.00);
        $mockTransaction2->shouldReceive('jsonSerialize')->andReturn([
            'amount' => 200.00
        ]);

        $this->mockRepository
            ->shouldReceive('findByFilters')
            ->with([
                'condominium_id' => $condominiumId,
                'status' => 'overdue'
            ])
            ->andReturn([$mockTransaction1, $mockTransaction2]);

        $this->mockRepository
            ->shouldReceive('countByFilters')
            ->with([
                'condominium_id' => $condominiumId
            ])
            ->andReturn(10);

        $this->mockLogger
            ->shouldReceive('info')
            ->with('Relatório de inadimplência gerado', Mockery::any());

        $report = $this->reportService->generateDelinquencyReport($condominiumId, $referenceDate);

        $this->assertArrayHasKey('total_delinquent_amount', $report);
        $this->assertArrayHasKey('delinquent_transactions', $report);
        $this->assertArrayHasKey('delinquency_rate', $report);

        $this->assertEquals(700.00, $report['total_delinquent_amount']);
        $this->assertCount(2, $report['delinquent_transactions']);
        $this->assertEquals(7.0, $report['delinquency_rate']);
    }

    public function testExportReport() {
        $reportData = [
            'total_revenue' => 1500.00,
            'categories' => [
                'aluguel' => 1000.00,
                'taxa_condominio' => 500.00
            ]
        ];

        $this->mockLogger
            ->shouldReceive('info')
            ->with('Relatório exportado', Mockery::any());

        $csvOutput = $this->reportService->exportReport($reportData, 'csv');
        $jsonOutput = $this->reportService->exportReport($reportData, 'json');

        $this->assertStringContainsString('total_revenue,categories', $csvOutput);
        $this->assertStringContainsString('1500', $csvOutput);
        $this->assertJson($jsonOutput);
    }

    public function testExportReportWithUnsupportedFormat() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Formato de exportação não suportado: xml');

        $this->reportService->exportReport([], 'xml');
    }
}
