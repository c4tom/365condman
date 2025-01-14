<?php
namespace CondMan\Tests\Unit;

use CondMan\Infrastructure\Configuration\ConfigurationService;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Mockery;

class ConfigurationServiceTest extends TestCase {
    private $wpdbMock;
    private $logHandler;
    private $logger;
    private $configService;

    protected function setUp(): void {
        $this->wpdbMock = Mockery::mock(\wpdb::class);
        $this->logHandler = new TestHandler();
        $this->logger = new Logger('test');
        $this->logger->pushHandler($this->logHandler);

        $this->configService = new ConfigurationService(
            $this->wpdbMock,
            $this->logger
        );

        // Limpar configurações de teste
        delete_option('365condman_test_key');
    }

    public function testSetAndGetConfiguration() {
        $result = $this->configService->set('test_key', 'test_value');
        $this->assertTrue($result);

        $value = $this->configService->get('test_key');
        $this->assertEquals('test_value', $value);

        $this->assertTrue($this->logHandler->hasInfoRecords());
    }

    public function testGetConfigurationWithDefault() {
        $value = $this->configService->get('non_existent_key', 'default_value');
        $this->assertEquals('default_value', $value);
    }

    public function testDeleteConfiguration() {
        $this->configService->set('test_key', 'test_value');
        $result = $this->configService->delete('test_key');
        $this->assertTrue($result);

        $value = $this->configService->get('test_key');
        $this->assertNull($value);
    }

    public function testHasConfiguration() {
        $this->configService->set('test_key', 'test_value');
        $exists = $this->configService->has('test_key');
        $this->assertTrue($exists);

        $this->configService->delete('test_key');
        $exists = $this->configService->has('test_key');
        $this->assertFalse($exists);
    }

    public function testListAllConfigurations() {
        $this->configService->set('test_key1', 'value1');
        $this->configService->set('test_key2', 'value2');

        $this->wpdbMock
            ->shouldReceive('prepare')
            ->andReturn('');

        $this->wpdbMock
            ->shouldReceive('get_results')
            ->andReturn([
                [
                    'option_name' => '365condman_test_key1',
                    'option_value' => 'value1'
                ],
                [
                    'option_name' => '365condman_test_key2',
                    'option_value' => 'value2'
                ]
            ]);

        $configs = $this->configService->listAll();
        $this->assertCount(2, $configs);
        $this->assertEquals('value1', $configs['test_key1']);
        $this->assertEquals('value2', $configs['test_key2']);
    }

    public function testRestoreDefaults() {
        $result = $this->configService->restoreDefaults();
        $this->assertTrue($result);

        $smtpHost = $this->configService->get('smtp_host');
        $this->assertEquals('localhost', $smtpHost);
    }

    public function testExportAndImportConfigurations() {
        $this->configService->set('test_export_key', 'export_value');

        $exportedConfigs = $this->configService->export();
        $this->assertArrayHasKey('test_export_key', $exportedConfigs);

        // Limpar configuração anterior
        $this->configService->delete('test_export_key');

        $result = $this->configService->import($exportedConfigs);
        $this->assertTrue($result);

        $importedValue = $this->configService->get('test_export_key');
        $this->assertEquals('export_value', $importedValue);
    }

    protected function tearDown(): void {
        Mockery::close();
        delete_option('365condman_test_key');
        delete_option('365condman_test_export_key');
    }
}
