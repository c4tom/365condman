<?php
namespace CondMan\Tests\Unit;

use CondMan\Domain\Services\CondominiumService;
use CondMan\Domain\Interfaces\ConfigurationInterface;
use PHPUnit\Framework\TestCase;
use Mockery;

class CondominiumServiceTest extends TestCase {
    private $configMock;
    private $service;

    protected function setUp(): void {
        $this->configMock = Mockery::mock(ConfigurationInterface::class);
        $this->service = new CondominiumService($this->configMock);
    }

    public function testCreateCondominium() {
        $data = [
            'name' => 'Residencial Teste',
            'address' => 'Rua Exemplo, 123',
            'city' => 'São Paulo'
        ];

        $result = $this->service->create($data);
        
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    public function testCreateCondominiumWithInvalidData() {
        $this->expectException(\InvalidArgumentException::class);
        
        $data = [
            'name' => '' // Nome vazio
        ];

        $this->service->create($data);
    }

    protected function tearDown(): void {
        Mockery::close();
    }
}
