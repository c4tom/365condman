<?php
namespace CondMan\Domain\Interfaces;

interface AdvancedNotificationInterface {
    /**
     * Envia notificação por múltiplos canais
     * @param int $userId ID do usuário
     * @param string $title Título da notificação
     * @param string $message Mensagem da notificação
     * @param array $channels Canais de notificação
     * @param array $metadata Metadados adicionais
     */
    public function sendAdvancedNotification(
        int $userId, 
        string $title, 
        string $message, 
        array $channels = ['dashboard', 'email'],
        array $metadata = []
    ): bool;

    /**
     * Agenda uma notificação para envio futuro
     * @param int $userId ID do usuário
     * @param string $title Título da notificação
     * @param string $message Mensagem da notificação
     * @param \DateTime $sendAt Data e hora para envio
     * @param array $channels Canais de notificação
     * @param array $metadata Metadados adicionais
     */
    public function scheduleNotification(
        int $userId, 
        string $title, 
        string $message, 
        \DateTime $sendAt,
        array $channels = ['dashboard', 'email'],
        array $metadata = []
    ): bool;

    /**
     * Cancela uma notificação agendada
     * @param string $notificationId Identificador da notificação
     */
    public function cancelScheduledNotification(string $notificationId): bool;

    /**
     * Busca notificações por filtros
     * @param array $filters Filtros para busca de notificações
     * @return array Lista de notificações
     */
    public function findNotificationsByFilters(array $filters): array;

    /**
     * Marca notificação como lida
     * @param string $notificationId Identificador da notificação
     */
    public function markNotificationAsRead(string $notificationId): bool;

    /**
     * Obtém configurações de notificação do usuário
     * @param int $userId ID do usuário
     * @return array Configurações de notificação
     */
    public function getUserNotificationPreferences(int $userId): array;

    /**
     * Atualiza preferências de notificação do usuário
     * @param int $userId ID do usuário
     * @param array $preferences Novas preferências de notificação
     */
    public function updateUserNotificationPreferences(
        int $userId, 
        array $preferences
    ): bool;
}
