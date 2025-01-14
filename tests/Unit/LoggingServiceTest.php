<?php
namespace CondMan\Tests\Unit;

use CondMan\Infrastructure\Logging\LoggingService;
use CondMan\Domain\Interfaces\ConfigurationInterface;
use PHPUnit\Framework\TestCase;
use Mockery;
use Monolog\Logger;

class LoggingServiceTest extends TestCase {
    private $configMock;
    private $loggingService;

    protected function setUp(): void {
        $this->configMock = Mockery::mock(ConfigurationInterface::class);
        
        $this->configMock
            ->shouldReceive('get')
            ->with('log_level', 'info')
            ->andReturn('info');

        $this->configMock
            ->shouldReceive('get')
            ->with('log_max_files', 30)
            ->andReturn(30);

        $this->loggingService = new LoggingService($this->configMock);
    }

    public function testDebugLog() {
        $this->expectNotToPerformAssertions();
        $this->loggingService->debug('Mensagem de debug', ['key' => 'value']);
    }

    public function testInfoLog() {
        $this->expectNotToPerformAssertions();
        $this->loggingService->info('Mensagem de informação', ['key' => 'value']);
    }

    public function testWarningLog() {
        $this->expectNotToPerformAssertions();
        $this->loggingService->warning('Mensagem de aviso', ['key' => 'value']);
    }

    public function testErrorLog() {
        $this->expectNotToPerformAssertions();
        $this->loggingService->error('Mensagem de erro', ['key' => 'value']);
    }

    public function testCriticalLog() {
        $this->expectNotToPerformAssertions();
        $this->loggingService->critical('Mensagem crítica', ['key' => 'value']);
    }

    public function testLogException() {
        $exception = new \Exception('Erro de teste');
        $this->expectNotToPerformAssertions();
        $this->loggingService->logException($exception);
    }

    public function testSanitizeContextWithSensitiveData() {
        $context = [
            'password' => 'secret123',
            'api_key' => 'abc123',
            'normal_key' => 'normal_value'
        ];

        $this->expectNotToPerformAssertions();
        $this->loggingService->error('Teste de sanitização', $context);
    }

    public function testCleanupLogs() {
        $this->expectNotToPerformAssertions();
        $this->loggingService->cleanupLogs(7);
    }

    protected function tearDown(): void {
        Mockery::close();
    }
}
