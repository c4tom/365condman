<?php
namespace CondMan\Infrastructure\Repositories;

use CondMan\Domain\Repositories\SubscriptionRepositoryInterface;
use CondMan\Domain\Entities\Subscription;
use CondMan\Domain\Interfaces\LoggerInterface;
use DateTime;
use wpdb;
use Exception;

class WordPressSubscriptionRepository implements SubscriptionRepositoryInterface {
    private wpdb $wpdb;
    private LoggerInterface $logger;
    private string $subscriptionTable;

    public function __construct(
        wpdb $wpdb, 
        LoggerInterface $logger,
        ?string $subscriptionTable = null
    ) {
        $this->wpdb = $wpdb;
        $this->logger = $logger;
        $this->subscriptionTable = $subscriptionTable ?? $this->wpdb->prefix . 'user_subscriptions';
    }

    public function save(Subscription $subscription): Subscription {
        try {
            $data = [
                'user_id' => $subscription->getUserId(),
                'type' => $subscription->getType(),
                'preferences' => json_encode($subscription->getPreferences()),
                'is_active' => $subscription->isActive() ? 1 : 0,
                'notification_channels' => json_encode($subscription->getNotificationChannels())
            ];

            if ($subscription->getId() === null) {
                // Inserir nova assinatura
                $this->wpdb->insert($this->subscriptionTable, $data);
                $subscriptionId = $this->wpdb->insert_id;
            } else {
                // Atualizar assinatura existente
                $this->wpdb->update(
                    $this->subscriptionTable, 
                    $data, 
                    ['id' => $subscription->getId()]
                );
                $subscriptionId = $subscription->getId();
            }

            $this->logger->info('Assinatura salva', [
                'subscription_id' => $subscriptionId,
                'user_id' => $subscription->getUserId(),
                'type' => $subscription->getType()
            ]);

            return $this->findById($subscriptionId);
        } catch (Exception $e) {
            $this->logger->error('Erro ao salvar assinatura', [
                'error' => $e->getMessage(),
                'user_id' => $subscription->getUserId()
            ]);
            throw $e;
        }
    }

    public function findById(int $id): ?Subscription {
        try {
            $result = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->subscriptionTable} WHERE id = %d", 
                    $id
                ), 
                ARRAY_A
            );

            return $result ? $this->createSubscriptionFromDbResult($result) : null;
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar assinatura por ID', [
                'error' => $e->getMessage(),
                'subscription_id' => $id
            ]);
            throw $e;
        }
    }

    public function findByFilters(array $filters): array {
        try {
            $conditions = [];
            $values = [];

            if (isset($filters['user_id'])) {
                $conditions[] = 'user_id = %d';
                $values[] = $filters['user_id'];
            }

            if (isset($filters['type'])) {
                $conditions[] = 'type = %s';
                $values[] = $filters['type'];
            }

            if (isset($filters['is_active'])) {
                $conditions[] = 'is_active = %d';
                $values[] = $filters['is_active'] ? 1 : 0;
            }

            $query = "SELECT * FROM {$this->subscriptionTable}";
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }

            $query .= " ORDER BY created_at DESC";

            if (isset($filters['limit'])) {
                $query .= $this->wpdb->prepare(" LIMIT %d", $filters['limit']);
            }

            $results = $this->wpdb->get_results(
                $this->wpdb->prepare($query, $values), 
                ARRAY_A
            );

            return array_map(
                fn($result) => $this->createSubscriptionFromDbResult($result), 
                $results
            );
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar assinaturas', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    public function countByFilters(array $filters): int {
        try {
            $conditions = [];
            $values = [];

            if (isset($filters['user_id'])) {
                $conditions[] = 'user_id = %d';
                $values[] = $filters['user_id'];
            }

            if (isset($filters['type'])) {
                $conditions[] = 'type = %s';
                $values[] = $filters['type'];
            }

            if (isset($filters['is_active'])) {
                $conditions[] = 'is_active = %d';
                $values[] = $filters['is_active'] ? 1 : 0;
            }

            $query = "SELECT COUNT(*) FROM {$this->subscriptionTable}";
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }

            return (int) $this->wpdb->get_var(
                $this->wpdb->prepare($query, $values)
            );
        } catch (Exception $e) {
            $this->logger->error('Erro ao contar assinaturas', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    public function findUserSubscriptionByType(int $userId, string $type): ?Subscription {
        try {
            $result = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->subscriptionTable} WHERE user_id = %d AND type = %s", 
                    $userId,
                    $type
                ), 
                ARRAY_A
            );

            return $result ? $this->createSubscriptionFromDbResult($result) : null;
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar assinatura do usuário', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'type' => $type
            ]);
            throw $e;
        }
    }

    public function delete(int $subscriptionId): bool {
        try {
            $result = $this->wpdb->delete(
                $this->subscriptionTable, 
                ['id' => $subscriptionId]
            );

            $this->logger->info('Assinatura removida', [
                'subscription_id' => $subscriptionId
            ]);

            return $result !== false;
        } catch (Exception $e) {
            $this->logger->error('Erro ao remover assinatura', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId
            ]);
            throw $e;
        }
    }

    public function findActiveSubscribersForType(string $type): array {
        try {
            $results = $this->wpdb->get_col(
                $this->wpdb->prepare(
                    "SELECT user_id FROM {$this->subscriptionTable} WHERE type = %s AND is_active = 1", 
                    $type
                )
            );

            return array_map('intval', $results);
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar assinantes ativos', [
                'error' => $e->getMessage(),
                'type' => $type
            ]);
            throw $e;
        }
    }

    private function createSubscriptionFromDbResult(array $result): Subscription {
        return new Subscription(
            (int) $result['id'],
            (int) $result['user_id'],
            $result['type'],
            json_decode($result['preferences'] ?? '{}', true),
            (bool) $result['is_active'],
            new DateTime($result['created_at']),
            $result['updated_at'] ? new DateTime($result['updated_at']) : null,
            json_decode($result['notification_channels'] ?? '["email", "dashboard"]', true)
        );
    }
}
