<?php
namespace CondMan\Infrastructure\Notifications;

use CondMan\Domain\Interfaces\NotificationInterface;
use CondMan\Domain\Interfaces\ConfigurationInterface;

class EmailNotification implements NotificationInterface {
    private $config;
    private $logger;

    public function __construct(
        ConfigurationInterface $config, 
        LoggerInterface $logger = null
    ) {
        $this->config = $config;
        $this->logger = $logger ?? new NullLogger();
    }

    public function send(string $recipient, string $message, array $context = []): bool {
        try {
            $headers = $context['headers'] ?? [];
            $attachments = $context['attachments'] ?? [];

            $result = wp_mail(
                $recipient, 
                $context['subject'] ?? 'Notificação 365 Cond Man', 
                $message, 
                $headers, 
                $attachments
            );

            $this->log($recipient, $message, $result);
            return $result;
        } catch (\Exception $e) {
            $this->log($recipient, $message, false, $e->getMessage());
            return false;
        }
    }

    public function log(string $recipient, string $message, bool $status, string $error = null): void {
        $logMessage = sprintf(
            "Email para %s: %s (Status: %s) %s", 
            $recipient, 
            $message, 
            $status ? 'Enviado' : 'Falha',
            $error ? "Erro: {$error}" : ''
        );

        if ($this->logger) {
            $status ? $this->logger->info($logMessage) : $this->logger->error($logMessage);
        }
    }
}
