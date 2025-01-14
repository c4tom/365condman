<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Entities\FinancialTransaction;
use CondMan\Domain\Interfaces\LoggerInterface;
use CondMan\Domain\Repositories\FinancialTransactionRepositoryInterface;
use DateTime;
use Exception;

class FinancialTransactionService {
    private FinancialTransactionRepositoryInterface $repository;
    private LoggerInterface $logger;

    public function __construct(
        FinancialTransactionRepositoryInterface $repository,
        LoggerInterface $logger
    ) {
        $this->repository = $repository;
        $this->logger = $logger;
    }

    /**
     * Registra uma nova transação financeira
     */
    public function recordTransaction(FinancialTransaction $transaction): FinancialTransaction {
        try {
            if (!$transaction->isValid()) {
                throw new Exception("Transação financeira inválida");
            }

            $savedTransaction = $this->repository->save($transaction);
            
            $this->logger->info('Transação financeira registrada', [
                'transaction_id' => $savedTransaction->getId(),
                'amount' => $savedTransaction->getAmount(),
                'type' => $savedTransaction->getType()
            ]);

            return $savedTransaction;
        } catch (Exception $e) {
            $this->logger->error('Erro ao registrar transação', [
                'error' => $e->getMessage(),
                'transaction' => $transaction->jsonSerialize()
            ]);
            throw $e;
        }
    }

    /**
     * Busca transações por filtros
     */
    public function findTransactions(array $filters = []): array {
        try {
            $transactions = $this->repository->findByFilters($filters);
            
            $this->logger->info('Transações financeiras recuperadas', [
                'filter_count' => count($filters),
                'result_count' => count($transactions)
            ]);

            return $transactions;
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar transações', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza status de uma transação
     */
    public function updateTransactionStatus(int $transactionId, string $newStatus): FinancialTransaction {
        try {
            $transaction = $this->repository->findById($transactionId);
            
            if (!$transaction) {
                throw new Exception("Transação não encontrada");
            }

            $transaction->setStatus($newStatus);
            $updatedTransaction = $this->repository->save($transaction);

            $this->logger->info('Status de transação atualizado', [
                'transaction_id' => $transactionId,
                'new_status' => $newStatus
            ]);

            return $updatedTransaction;
        } catch (Exception $e) {
            $this->logger->error('Erro ao atualizar status da transação', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'new_status' => $newStatus
            ]);
            throw $e;
        }
    }

    /**
     * Calcula saldo total por categoria
     */
    public function calculateBalanceByCategory(int $condominiumId, ?DateTime $startDate = null, ?DateTime $endDate = null): array {
        try {
            $filters = [
                'condominium_id' => $condominiumId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ];

            $transactions = $this->repository->findByFilters($filters);
            
            $balances = [];
            foreach ($transactions as $transaction) {
                $category = $transaction->getCategory();
                $amount = $transaction->getAmount();

                $balances[$category] = ($balances[$category] ?? 0) + $amount;
            }

            $this->logger->info('Saldo calculado por categoria', [
                'condominium_id' => $condominiumId,
                'categories_count' => count($balances)
            ]);

            return $balances;
        } catch (Exception $e) {
            $this->logger->error('Erro ao calcular saldo por categoria', [
                'error' => $e->getMessage(),
                'condominium_id' => $condominiumId
            ]);
            throw $e;
        }
    }
}
