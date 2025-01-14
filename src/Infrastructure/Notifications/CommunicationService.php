<?php
namespace CondMan\Infrastructure\Notifications;

use CondMan\Domain\Interfaces\NotificationInterface;
use CondMan\Domain\Interfaces\ConfigurationInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class CommunicationService implements NotificationInterface {
    private $config;
    private $logger;
    private $mailer;

    public function __construct(
        ConfigurationInterface $config,
        Logger $logger = null,
        PHPMailer $mailer = null
    ) {
        $this->config = $config;
        $this->logger = $logger ?? $this->createLogger();
        $this->mailer = $mailer ?? $this->createMailer();
    }

    /**
     * Cria logger personalizado
     * @return Logger
     */
    private function createLogger(): Logger {
        $logger = new Logger('communication');
        $logger->pushHandler(
            new StreamHandler(
                WP_CONTENT_DIR . '/logs/365condman-communication.log', 
                Logger::INFO
            )
        );
        return $logger;
    }

    /**
     * Cria instância do PHPMailer
     * @return PHPMailer
     */
    private function createMailer(): PHPMailer {
        $mail = new PHPMailer(true);
        
        try {
            // Configurações SMTP
            $mail->isSMTP();
            $mail->Host = $this->config->get('smtp_host', 'localhost');
            $mail->SMTPAuth = true;
            $mail->Username = $this->config->get('smtp_username', '');
            $mail->Password = $this->config->get('smtp_password', '');
            $mail->SMTPSecure = $this->config->get('smtp_secure', 'tls');
            $mail->Port = $this->config->get('smtp_port', 587);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao configurar SMTP', [
                'message' => $e->getMessage()
            ]);
        }

        return $mail;
    }

    /**
     * Envia notificação
     * @param string $recipient Destinatário
     * @param string $message Mensagem
     * @param array $context Contexto adicional
     * @return bool Status do envio
     */
    public function send(string $recipient, string $message, array $context = []): bool {
        try {
            $this->mailer->clearAllRecipients();
            $this->mailer->setFrom(
                $this->config->get('notification_sender_email', 'noreply@condman.com'),
                $this->config->get('notification_sender_name', '365 Cond Man')
            );
            $this->mailer->addAddress($recipient);
            
            $this->mailer->isHTML($context['html'] ?? true);
            $this->mailer->Subject = $context['subject'] ?? 'Notificação 365 Cond Man';
            $this->mailer->Body = $message;

            $result = $this->mailer->send();
            $this->log($recipient, $message, $result, $context);

            return $result;
        } catch (PHPMailerException $e) {
            $this->logger->error('Falha no envio de email', [
                'recipient' => $recipient,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Registra log de notificação
     * @param string $recipient Destinatário
     * @param string $message Mensagem
     * @param bool $status Status do envio
     * @param array $context Contexto adicional
     */
    public function log(string $recipient, string $message, bool $status, array $context = []): void {
        $logMethod = $status ? 'info' : 'error';
        $this->logger->$logMethod('Notificação enviada', [
            'recipient' => $recipient,
            'status' => $status ? 'Sucesso' : 'Falha',
            'type' => $context['type'] ?? 'email',
            'subject' => $context['subject'] ?? 'Sem assunto'
        ]);
    }

    /**
     * Envia notificação SMS
     * @param string $phone Número de telefone
     * @param string $message Mensagem
     * @return bool Status do envio
     */
    public function sendSMS(string $phone, string $message): bool {
        // Implementação de envio de SMS
        // Pode usar serviço de terceiros como Twilio
        $this->logger->info('Tentativa de envio de SMS', [
            'phone' => $phone
        ]);

        return false; // Placeholder
    }

    /**
     * Envia notificação por WhatsApp
     * @param string $phone Número de telefone
     * @param string $message Mensagem
     * @return bool Status do envio
     */
    public function sendWhatsApp(string $phone, string $message): bool {
        // Implementação de envio por WhatsApp
        // Pode usar API do WhatsApp Business
        $this->logger->info('Tentativa de envio de WhatsApp', [
            'phone' => $phone
        ]);

        return false; // Placeholder
    }
}
