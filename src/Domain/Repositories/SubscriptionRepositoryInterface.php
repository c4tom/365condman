<?php
namespace CondMan\Domain\Repositories;

use CondMan\Domain\Entities\Subscription;

interface SubscriptionRepositoryInterface {
    /**
     * Salva uma nova assinatura ou atualiza uma existente
     */
    public function save(Subscription $subscription): Subscription;

    /**
     * Busca uma assinatura por ID
     */
    public function findById(int $id): ?Subscription;

    /**
     * Busca assinaturas por filtros
     * @param array $filters Filtros para busca de assinaturas
     * @return Subscription[]
     */
    public function findByFilters(array $filters): array;

    /**
     * Conta assinaturas por filtros
     * @param array $filters Filtros para contagem
     */
    public function countByFilters(array $filters): int;

    /**
     * Busca assinatura de um usuário por tipo
     */
    public function findUserSubscriptionByType(int $userId, string $type): ?Subscription;

    /**
     * Remove assinatura
     */
    public function delete(int $subscriptionId): bool;

    /**
     * Busca usuários com assinaturas ativas para um tipo específico
     * @param string $type Tipo de assinatura
     * @return int[] IDs dos usuários
     */
    public function findActiveSubscribersForType(string $type): array;
}
