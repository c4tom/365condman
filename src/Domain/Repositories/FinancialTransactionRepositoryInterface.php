<?php
namespace CondMan\Domain\Repositories;

use CondMan\Domain\Entities\FinancialTransaction;
use DateTime;

interface FinancialTransactionRepositoryInterface {
    /**
     * Salva uma transação financeira
     */
    public function save(FinancialTransaction $transaction): FinancialTransaction;

    /**
     * Busca transação por ID
     */
    public function findById(int $id): ?FinancialTransaction;

    /**
     * Busca transações por filtros
     * @param array $filters Filtros de busca
     * @return FinancialTransaction[]
     */
    public function findByFilters(array $filters): array;

    /**
     * Remove uma transação
     */
    public function delete(int $id): bool;

    /**
     * Conta transações por filtros
     */
    public function countByFilters(array $filters): int;

    /**
     * Calcula saldo total
     */
    public function calculateTotalBalance(int $condominiumId, ?DateTime $startDate = null, ?DateTime $endDate = null): float;
}
