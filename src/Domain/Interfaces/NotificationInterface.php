<?php
namespace CondMan\Domain\Interfaces;

interface NotificationInterface {
    /**
     * Envia notificação
     * @param string $recipient Destinatário
     * @param string $message Mensagem
     * @param array $context Contexto adicional
     * @return bool Status do envio
     */
    public function send(string $recipient, string $message, array $context = []): bool;

    /**
     * Registra log de notificação
     * @param string $recipient Destinatário
     * @param string $message Mensagem
     * @param bool $status Status do envio
     */
    public function log(string $recipient, string $message, bool $status): void;
}
