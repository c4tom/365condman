<?php
namespace CondMan\Infrastructure\Services;

use CondMan\Domain\Interfaces\NotificationInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use WP_User;

class WordPressNotificationService implements NotificationInterface {
    private LoggerInterface $logger;
    private string $logTable;

    public function __construct(LoggerInterface $logger, ?string $logTable = null) {
        $this->logger = $logger;
        $this->logTable = $logTable ?? $this->getDefaultLogTable();
    }

    /**
     * Envia notificação via WordPress
     */
    public function send(string $recipient, string $message, array $context = []): bool {
        try {
            // Suporte a múltiplos canais de notificação
            $notificationChannels = [
                'email' => fn() => $this->sendEmailNotification($recipient, $message, $context),
                'wordpress_admin' => fn() => $this->sendWordPressAdminNotification($recipient, $message, $context),
                'sms' => fn() => $this->sendSMSNotification($recipient, $message, $context)
            ];

            // Tenta enviar por todos os canais
            $results = array_map(fn($channel) => $channel(), $notificationChannels);

            // Retorna true se pelo menos um canal teve sucesso
            return in_array(true, $results, true);
        } catch (\Exception $e) {
            $this->logger->error('Erro no envio de notificação', [
                'recipient' => $recipient,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Registra log de notificação
     */
    public function log(string $recipient, string $message, bool $status): void {
        global $wpdb;

        $wpdb->insert(
            $this->logTable,
            [
                'recipient' => $recipient,
                'message' => $message,
                'status' => $status ? 'success' : 'failed',
                'created_at' => current_time('mysql')
            ]
        );
    }

    /**
     * Obtém tabela padrão de log
     */
    private function getDefaultLogTable(): string {
        global $wpdb;
        return $wpdb->prefix . 'condman_notification_logs';
    }

    /**
     * Envia notificação por e-mail
     */
    private function sendEmailNotification(string $recipient, string $message, array $context = []): bool {
        $user = $this->getUserByIdentifier($recipient);
        
        if (!$user || !$user->user_email) {
            return false;
        }

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $subject = $this->extractSubject($message);
        $htmlMessage = $this->formatMessageToHTML($message);

        return wp_mail(
            $user->user_email, 
            $subject, 
            $htmlMessage, 
            $headers, 
            $this->getAttachments($context)
        );
    }

    /**
     * Envia notificação via painel WordPress
     */
    private function sendWordPressAdminNotification(string $recipient, string $message, array $context = []): bool {
        if (!function_exists('wp_insert_post')) {
            return false;
        }

        $user = $this->getUserByIdentifier($recipient);
        
        if (!$user) {
            return false;
        }

        $notification = [
            'post_title' => $this->extractSubject($message),
            'post_content' => $message,
            'post_status' => 'publish',
            'post_type' => 'condman_notification'
        ];

        $notificationId = wp_insert_post($notification);

        if ($notificationId) {
            update_post_meta($notificationId, '_notification_recipient', $user->ID);
            update_post_meta($notificationId, '_notification_context', $context);
            return true;
        }

        return false;
    }

    /**
     * Envia notificação por SMS (placeholder)
     */
    private function sendSMSNotification(string $recipient, string $message, array $context = []): bool {
        // Implementação futura ou integração com serviço de SMS
        return false;
    }

    /**
     * Recupera usuário por identificador
     */
    private function getUserByIdentifier(string $identifier): ?WP_User {
        $userId = filter_var($identifier, FILTER_VALIDATE_INT);
        return $userId ? get_user_by('ID', $userId) : null;
    }

    /**
     * Extrai assunto da mensagem
     */
    private function extractSubject(string $message): string {
        $lines = explode("\n", $message);
        return trim(str_replace('*', '', $lines[0]));
    }

    /**
     * Formata mensagem para HTML
     */
    private function formatMessageToHTML(string $message): string {
        $lines = explode("\n", $message);
        $formattedLines = array_map(function($line) {
            if (strpos($line, '*') === 0 && strrpos($line, '*') === strlen($line) - 1) {
                return "<h2>" . trim(str_replace('*', '', $line)) . "</h2>";
            }
            return "<p>{$line}</p>";
        }, $lines);

        return implode('', $formattedLines);
    }

    /**
     * Obtém anexos da notificação
     */
    private function getAttachments(array $context): array {
        // Lógica futura para adicionar anexos baseados no contexto
        return [];
    }
}
