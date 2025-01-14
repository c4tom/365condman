<?php
namespace CondMan\Tests\Unit;

use CondMan\Domain\Services\UnitService;
use CondMan\Domain\Interfaces\ConfigurationInterface;
use CondMan\Domain\Entities\Unit;
use PHPUnit\Framework\TestCase;
use Mockery;

class UnitServiceTest extends TestCase {
    private $configMock;
    private $wpdbMock;
    private $service;

    protected function setUp(): void {
        $this->configMock = Mockery::mock(ConfigurationInterface::class);
        $this->wpdbMock = Mockery::mock(\wpdb::class);
        
        $this->service = new UnitService(
            $this->configMock, 
            $this->wpdbMock
        );
    }

    public function testCreateUnit() {
        // Configurar mock para inserção
        $this->wpdbMock
            ->shouldReceive('insert')
            ->once()
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
                'number' => '101',
                'type' => 'residential'
            ]);

        $data = [
            'condominium_id' => 1,
            'number' => '101',
            'type' => 'residential'
        ];

        $unit = $this->service->create($data);
        
        $this->assertInstanceOf(Unit::class, $unit);
        $this->assertEquals(1, $unit->getId());
        $this->assertEquals(1, $unit->getCondominiumId());
        $this->assertEquals('101', $unit->getNumber());
    }

    public function testCreateUnitWithInvalidData() {
        $this->expectException(\InvalidArgumentException::class);
        
        $data = [
            'number' => '101' // Sem condominium_id
        ];

        $this->service->create($data);
    }

    protected function tearDown(): void {
        Mockery::close();
    }
}
