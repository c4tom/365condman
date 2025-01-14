<?php
namespace CondMan\Tests\Unit;

use CondMan\Infrastructure\Integrations\ExternalIntegrationService;
use CondMan\Domain\Interfaces\ConfigurationInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Mockery;

class ExternalIntegrationServiceTest extends TestCase {
    private $configMock;
    private $httpClientMock;
    private $logHandler;
    private $logger;
    private $integrationService;

    protected function setUp(): void {
        $this->configMock = Mockery::mock(ConfigurationInterface::class);
        $this->httpClientMock = Mockery::mock(Client::class);
        
        $this->logHandler = new TestHandler();
        $this->logger = new Logger('test');
        $this->logger->pushHandler($this->logHandler);

        $this->configMock
            ->shouldReceive('get')
            ->with('external_integration_url')
            ->andReturn('https://example.com/api/integrate');
        
        $this->configMock
            ->shouldReceive('get')
            ->with('external_integration_token')
            ->andReturn('test_token');

        $this->integrationService = new ExternalIntegrationService(
            $this->configMock,
            $this->logger,
            $this->httpClientMock
        );
    }

    public function testSuccessfulIntegration() {
        $data = [
            'condominium_id' => 1,
            'unit_id' => 101,
            'type' => 'residential'
        ];

        $this->httpClientMock
            ->shouldReceive('post')
            ->once()
            ->andReturn(new Response(200));

        $result = $this->integrationService->integrate($data);
        
        $this->assertTrue($result);
        $this->assertTrue($this->logHandler->hasInfoRecords());
    }

    public function testIntegrationWithInvalidData() {
        $data = [
            'condominium_id' => null,
            'unit_id' => null
        ];

        $result = $this->integrationService->integrate($data);
        
        $this->assertFalse($result);
        $this->assertTrue($this->logHandler->hasWarningRecords());
    }

    protected function tearDown(): void {
        Mockery::close();
    }
}
