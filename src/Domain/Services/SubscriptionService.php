<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Entities\Subscription;
use CondMan\Domain\Repositories\SubscriptionRepositoryInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use CondMan\Domain\Interfaces\NotificationInterface;
use DateTime;
use Exception;

class SubscriptionService {
    private SubscriptionRepositoryInterface $repository;
    private LoggerInterface $logger;
    private NotificationInterface $notificationService;

    public function __construct(
        SubscriptionRepositoryInterface $repository,
        LoggerInterface $logger,
        NotificationInterface $notificationService
    ) {
        $this->repository = $repository;
        $this->logger = $logger;
        $this->notificationService = $notificationService;
    }

    /**
     * Cria uma nova assinatura
     */
    public function createSubscription(
        int $userId,
        string $type = 'communication',
        array $preferences = [],
        bool $isActive = true,
        array $notificationChannels = ['email', 'dashboard']
    ): Subscription {
        try {
            // Verificar se já existe uma assinatura do usuário para este tipo
            $existingSubscription = $this->repository->findUserSubscriptionByType($userId, $type);

            if ($existingSubscription) {
                throw new Exception("Usuário já possui uma assinatura para este tipo");
            }

            $subscription = new Subscription(
                null,
                $userId,
                $type,
                $preferences,
                $isActive,
                null,
                null,
                $notificationChannels
            );

            $savedSubscription = $this->repository->save($subscription);

            $this->logger->info('Assinatura criada', [
                'user_id' => $userId,
                'type' => $type
            ]);

            return $savedSubscription;
        } catch (Exception $e) {
            $this->logger->error('Erro ao criar assinatura', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'type' => $type
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza uma assinatura existente
     */
    public function updateSubscription(
        int $subscriptionId,
        array $preferences = [],
        ?bool $isActive = null,
        ?array $notificationChannels = null
    ): Subscription {
        try {
            $subscription = $this->repository->findById($subscriptionId);

            if (!$subscription) {
                throw new Exception("Assinatura não encontrada");
            }

            if (!empty($preferences)) {
                $subscription->updatePreferences($preferences);
            }

            if ($isActive !== null) {
                $subscription->toggleActive($isActive);
            }

            if ($notificationChannels !== null) {
                $subscription->updateNotificationChannels($notificationChannels);
            }

            $savedSubscription = $this->repository->save($subscription);

            $this->logger->info('Assinatura atualizada', [
                'subscription_id' => $subscriptionId
            ]);

            return $savedSubscription;
        } catch (Exception $e) {
            $this->logger->error('Erro ao atualizar assinatura', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId
            ]);
            throw $e;
        }
    }

    /**
     * Busca assinatura por ID
     */
    public function getSubscriptionById(int $subscriptionId): ?Subscription {
        try {
            $subscription = $this->repository->findById($subscriptionId);

            $this->logger->info('Assinatura recuperada', [
                'subscription_id' => $subscriptionId
            ]);

            return $subscription;
        } catch (Exception $e) {
            $this->logger->error('Erro ao recuperar assinatura', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId
            ]);
            throw $e;
        }
    }

    /**
     * Busca assinatura do usuário por tipo
     */
    public function getUserSubscriptionByType(int $userId, string $type): ?Subscription {
        try {
            $subscription = $this->repository->findUserSubscriptionByType($userId, $type);

            $this->logger->info('Assinatura do usuário recuperada', [
                'user_id' => $userId,
                'type' => $type
            ]);

            return $subscription;
        } catch (Exception $e) {
            $this->logger->error('Erro ao recuperar assinatura do usuário', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'type' => $type
            ]);
            throw $e;
        }
    }

    /**
     * Busca assinaturas por filtros
     */
    public function findSubscriptionsByFilters(array $filters): array {
        try {
            $subscriptions = $this->repository->findByFilters($filters);

            $this->logger->info('Assinaturas recuperadas por filtros', [
                'filters' => $filters,
                'count' => count($subscriptions)
            ]);

            return $subscriptions;
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar assinaturas', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    /**
     * Remove uma assinatura
     */
    public function deleteSubscription(int $subscriptionId): bool {
        try {
            $result = $this->repository->delete($subscriptionId);

            $this->logger->info('Assinatura removida', [
                'subscription_id' => $subscriptionId
            ]);

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Erro ao remover assinatura', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId
            ]);
            throw $e;
        }
    }

    /**
     * Notifica assinantes de um tipo específico
     */
    public function notifySubscribers(
        string $type, 
        string $title, 
        string $message,
        array $metadata = []
    ): void {
        try {
            $subscriberIds = $this->repository->findActiveSubscribersForType($type);

            foreach ($subscriberIds as $subscriberId) {
                $subscription = $this->repository->findUserSubscriptionByType($subscriberId, $type);

                if ($subscription) {
                    foreach ($subscription->getNotificationChannels() as $channel) {
                        switch ($channel) {
                            case 'email':
                                $this->notificationService->sendEmail(
                                    $subscriberId, 
                                    $title, 
                                    $message
                                );
                                break;
                            case 'dashboard':
                                $this->notificationService->sendDashboardNotification(
                                    $subscriberId, 
                                    $title, 
                                    $message,
                                    $metadata
                                );
                                break;
                        }
                    }
                }
            }

            $this->logger->info('Notificação enviada para assinantes', [
                'type' => $type,
                'subscribers_count' => count($subscriberIds)
            ]);
        } catch (Exception $e) {
            $this->logger->error('Erro ao notificar assinantes', [
                'error' => $e->getMessage(),
                'type' => $type
            ]);
            throw $e;
        }
    }
}
