<?php
namespace CondMan\Tests\Unit;

use CondMan\Infrastructure\Migrations\CreateInvoicesTableMigration;
use CondMan\Domain\Interfaces\ConfigurationInterface;
use PHPUnit\Framework\TestCase;
use Mockery;

class CreateInvoicesTableMigrationTest extends TestCase {
    private $wpdbMock;
    private $configMock;
    private $migration;

    protected function setUp(): void {
        $this->wpdbMock = Mockery::mock(\wpdb::class);
        $this->configMock = Mockery::mock(ConfigurationInterface::class);

        $this->wpdbMock
            ->shouldReceive('get_charset_collate')
            ->andReturn('DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->wpdbMock
            ->shouldReceive('prefix')
            ->andReturn('wp_');

        $this->migration = new CreateInvoicesTableMigration(
            $this->wpdbMock,
            $this->configMock
        );
    }

    public function testMigrationUp() {
        // Simular verificação de tabelas
        $this->wpdbMock
            ->shouldReceive('get_var')
            ->twice()
            ->andReturn('wp_365condman_invoices', 'wp_365condman_invoice_items');

        // Simular criação de tabelas
        $this->wpdbMock
            ->shouldReceive('query')
            ->twice();

        $result = $this->migration->up();
        $this->assertTrue($result);
    }

    public function testMigrationDown() {
        // Simular remoção de tabelas
        $this->wpdbMock
            ->shouldReceive('query')
            ->twice();

        $result = $this->migration->down();
        $this->assertTrue($result);
    }

    public function testIsApplied() {
        // Simular verificação de tabelas existentes
        $this->wpdbMock
            ->shouldReceive('get_var')
            ->twice()
            ->andReturn('wp_365condman_invoices', 'wp_365condman_invoice_items');

        $result = $this->migration->isApplied();
        $this->assertTrue($result);
    }

    protected function tearDown(): void {
        Mockery::close();
    }
}
