<?php
namespace CondMan\Tests\Unit;

use CondMan\Infrastructure\Migrations\MigrationService;
use CondMan\Domain\Interfaces\ConfigurationInterface;
use PHPUnit\Framework\TestCase;
use Mockery;

class MigrationServiceTest extends TestCase {
    private $wpdbMock;
    private $configMock;
    private $migrationService;

    protected function setUp(): void {
        $this->wpdbMock = Mockery::mock(\wpdb::class);
        $this->configMock = Mockery::mock(ConfigurationInterface::class);

        $this->wpdbMock
            ->shouldReceive('get_charset_collate')
            ->andReturn('');

        $this->wpdbMock
            ->shouldReceive('prefix')
            ->andReturn('wp_');

        $this->migrationService = new MigrationService(
            $this->wpdbMock, 
            $this->configMock
        );
    }

    public function testRunMigrations() {
        $this->wpdbMock
            ->shouldReceive('insert')
            ->times(1);

        $this->wpdbMock
            ->shouldReceive('get_var')
            ->times(2)
            ->andReturn(null);

        $results = $this->migrationService->runMigrations();
        
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    public function testRollbackMigrations() {
        $this->wpdbMock
            ->shouldReceive('get_var')
            ->times(2)
            ->andReturn('wp_365condman_units');

        $results = $this->migrationService->rollbackMigrations();
        
        $this->assertIsArray($results);
    }

    protected function tearDown(): void {
        Mockery::close();
    }
}
