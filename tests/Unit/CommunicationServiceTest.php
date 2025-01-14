<?php
namespace CondMan\Tests\Unit;

use CondMan\Infrastructure\Notifications\CommunicationService;
use CondMan\Domain\Interfaces\ConfigurationInterface;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\TestCase;
use Mockery;

class CommunicationServiceTest extends TestCase {
    private $configMock;
    private $logHandler;
    private $logger;
    private $mailerMock;
    private $communicationService;

    protected function setUp(): void {
        $this->configMock = Mockery::mock(ConfigurationInterface::class);
        $this->logHandler = new TestHandler();
        $this->logger = new Logger('test');
        $this->logger->pushHandler($this->logHandler);
        $this->mailerMock = Mockery::mock(PHPMailer::class);

        $this->configMock
            ->shouldReceive('get')
            ->with('notification_sender_email')
            ->andReturn('test@example.com');

        $this->configMock
            ->shouldReceive('get')
            ->with('notification_sender_name')
            ->andReturn('Test Sender');

        $this->communicationService = new CommunicationService(
            $this->configMock,
            $this->logger,
            $this->mailerMock
        );
    }

    public function testSuccessfulEmailSend() {
        $this->mailerMock
            ->shouldReceive('clearAllRecipients')
            ->once();

        $this->mailerMock
            ->shouldReceive('setFrom')
            ->once();

        $this->mailerMock
            ->shouldReceive('addAddress')
            ->once();

        $this->mailerMock
            ->shouldReceive('isHTML')
            ->once();

        $this->mailerMock
            ->shouldReceive('send')
            ->once()
            ->andReturn(true);

        $result = $this->communicationService->send(
            'recipient@example.com', 
            'Mensagem de teste',
            ['subject' => 'Teste']
        );
        
        $this->assertTrue($result);
        $this->assertTrue($this->logHandler->hasInfoRecords());
    }

    public function testFailedEmailSend() {
        $this->mailerMock
            ->shouldReceive('clearAllRecipients')
            ->once();

        $this->mailerMock
            ->shouldReceive('setFrom')
            ->once();

        $this->mailerMock
            ->shouldReceive('addAddress')
            ->once();

        $this->mailerMock
            ->shouldReceive('isHTML')
            ->once();

        $this->mailerMock
            ->shouldReceive('send')
            ->once()
            ->andReturn(false);

        $result = $this->communicationService->send(
            'recipient@example.com', 
            'Mensagem de teste',
            ['subject' => 'Teste']
        );
        
        $this->assertFalse($result);
        $this->assertTrue($this->logHandler->hasErrorRecords());
    }

    public function testSMSNotification() {
        $result = $this->communicationService->sendSMS(
            '+5511999999999', 
            'Mensagem SMS'
        );
        
        $this->assertFalse($result);
        $this->assertTrue($this->logHandler->hasInfoRecords());
    }

    protected function tearDown(): void {
        Mockery::close();
    }
}
