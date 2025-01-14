<?php
namespace CondMan\Tests\Unit;

use CondMan\Domain\Services\FinancialService;
use CondMan\Domain\Interfaces\ConfigurationInterface;
use CondMan\Infrastructure\Notifications\CommunicationService;
use CondMan\Domain\Entities\Invoice;
use PHPUnit\Framework\TestCase;
use Mockery;

class FinancialServiceTest extends TestCase {
    private $configMock;
    private $wpdbMock;
    private $communicationServiceMock;
    private $financialService;

    protected function setUp(): void {
        $this->configMock = Mockery::mock(ConfigurationInterface::class);
        $this->wpdbMock = Mockery::mock(\wpdb::class);
        $this->communicationServiceMock = Mockery::mock(CommunicationService::class);

        $this->configMock
            ->shouldReceive('get')
            ->with('default_notification_email')
            ->andReturn('test@example.com');

        $this->wpdbMock
            ->shouldReceive('prefix')
            ->andReturn('wp_');

        $this->financialService = new FinancialService(
            $this->configMock,
            $this->wpdbMock,
            $this->communicationServiceMock
        );
    }

    public function testGenerateInvoice() {
        // Configurar mock para inserção
        $this->wpdbMock
            ->shouldReceive('insert')
            ->times(2)
            ->andReturn(true);

        $this->wpdbMock
            ->shouldReceive('insert_id')
            ->once()
            ->andReturn(1);

        $this->wpdbMock
            ->shouldReceive('get_row')
            ->once()
            ->andReturn([
                'id' => 1,
                'condominium_id' => 1,
                'unit_id' => 101,
                'reference_month' => '01',
                'reference_year' => '2024',
                'total_amount' => 500.00,
                'status' => 'pending'
            ]);

        $this->communicationServiceMock
            ->shouldReceive('send')
            ->once();

        $data = [
            'condominium_id' => 1,
            'unit_id' => 101,
            'reference_month' => '01',
            'reference_year' => '2024',
            'due_date' => '2024-01-31',
            'items' => [
                [
                    'description' => 'Taxa de condomínio',
                    'amount' => 400.00,
                    'type' => 'monthly'
                ],
                [
                    'description' => 'Fundo de reserva',
                    'amount' => 100.00,
                    'type' => 'reserve'
                ]
            ]
        ];

        $invoice = $this->financialService->generateInvoice($data);
        
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals(1, $invoice->getId());
        $this->assertEquals(500.00, $invoice->getTotalAmount());
        $this->assertEquals('pending', $invoice->getStatus());
    }

    public function testGenerateInvoiceWithInvalidData() {
        $this->expectException(\InvalidArgumentException::class);
        
        $data = [
            'reference_month' => '01' // Sem condominium_id e unit_id
        ];

        $this->financialService->generateInvoice($data);
    }

    protected function tearDown(): void {
        Mockery::close();
    }
}
