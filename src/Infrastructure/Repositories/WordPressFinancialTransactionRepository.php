<?php
namespace CondMan\Infrastructure\Repositories;

use CondMan\Domain\Repositories\FinancialTransactionRepositoryInterface;
use CondMan\Domain\Entities\FinancialTransaction;
use CondMan\Domain\Interfaces\LoggerInterface;
use DateTime;
use Exception;
use wpdb;

class WordPressFinancialTransactionRepository implements FinancialTransactionRepositoryInterface {
    private wpdb $wpdb;
    private LoggerInterface $logger;
    private string $table;

    public function __construct(wpdb $wpdb, LoggerInterface $logger) {
        $this->wpdb = $wpdb;
        $this->logger = $logger;
        $this->table = $this->wpdb->prefix . 'financial_transactions';
    }

    public function save(FinancialTransaction $transaction): FinancialTransaction {
        try {
            $data = [
                'condominium_id' => $transaction->getCondominiumId(),
                'amount' => $transaction->getAmount(),
                'type' => $transaction->getType(),
                'category' => $transaction->getCategory(),
                'description' => $transaction->getDescription(),
                'date' => $transaction->getDate()->format('Y-m-d H:i:s'),
                'status' => $transaction->getStatus(),
                'invoice_id' => $transaction->getInvoiceId(),
                'payment_id' => $transaction->getPaymentId(),
                'reference' => $transaction->getReference(),
                'metadata' => json_encode($transaction->getMetadata() ?? [])
            ];

            if ($transaction->getId()) {
                $this->wpdb->update(
                    $this->table, 
                    $data, 
                    ['id' => $transaction->getId()]
                );
            } else {
                $this->wpdb->insert($this->table, $data);
                $data['id'] = $this->wpdb->insert_id;
            }

            $this->logger->info('Transação financeira salva', [
                'transaction_id' => $data['id'],
                'amount' => $data['amount']
            ]);

            return new FinancialTransaction($data);
        } catch (Exception $e) {
            $this->logger->error('Erro ao salvar transação', [
                'error' => $e->getMessage(),
                'transaction' => $transaction->jsonSerialize()
            ]);
            throw $e;
        }
    }

    public function findById(int $id): ?FinancialTransaction {
        try {
            $row = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->table} WHERE id = %d", 
                    $id
                ), 
                ARRAY_A
            );

            if (!$row) return null;

            $row['metadata'] = json_decode($row['metadata'], true);
            $row['date'] = new DateTime($row['date']);

            return new FinancialTransaction($row);
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar transação', [
                'error' => $e->getMessage(),
                'transaction_id' => $id
            ]);
            throw $e;
        }
    }

    public function findByFilters(array $filters): array {
        try {
            $conditions = [];
            $params = [];

            if (isset($filters['condominium_id'])) {
                $conditions[] = 'condominium_id = %d';
                $params[] = $filters['condominium_id'];
            }

            if (isset($filters['type'])) {
                $conditions[] = 'type = %s';
                $params[] = $filters['type'];
            }

            if (isset($filters['category'])) {
                $conditions[] = 'category = %s';
                $params[] = $filters['category'];
            }

            if (isset($filters['start_date'])) {
                $conditions[] = 'date >= %s';
                $params[] = $filters['start_date']->format('Y-m-d H:i:s');
            }

            if (isset($filters['end_date'])) {
                $conditions[] = 'date <= %s';
                $params[] = $filters['end_date']->format('Y-m-d H:i:s');
            }

            $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
            $query = $this->wpdb->prepare(
                "SELECT * FROM {$this->table} {$where}", 
                $params
            );

            $results = $this->wpdb->get_results($query, ARRAY_A);

            $transactions = array_map(function($row) {
                $row['metadata'] = json_decode($row['metadata'], true);
                $row['date'] = new DateTime($row['date']);
                return new FinancialTransaction($row);
            }, $results);

            return $transactions;
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar transações', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    public function delete(int $id): bool {
        try {
            $result = $this->wpdb->delete($this->table, ['id' => $id]);

            $this->logger->info('Transação financeira excluída', [
                'transaction_id' => $id,
                'deleted' => $result !== false
            ]);

            return $result !== false;
        } catch (Exception $e) {
            $this->logger->error('Erro ao excluir transação', [
                'error' => $e->getMessage(),
                'transaction_id' => $id
            ]);
            throw $e;
        }
    }

    public function countByFilters(array $filters): int {
        try {
            $conditions = [];
            $params = [];

            if (isset($filters['condominium_id'])) {
                $conditions[] = 'condominium_id = %d';
                $params[] = $filters['condominium_id'];
            }

            $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
            $query = $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table} {$where}", 
                $params
            );

            return (int) $this->wpdb->get_var($query);
        } catch (Exception $e) {
            $this->logger->error('Erro ao contar transações', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    public function calculateTotalBalance(int $condominiumId, ?DateTime $startDate = null, ?DateTime $endDate = null): float {
        try {
            $conditions = ['condominium_id = %d'];
            $params = [$condominiumId];

            if ($startDate) {
                $conditions[] = 'date >= %s';
                $params[] = $startDate->format('Y-m-d H:i:s');
            }

            if ($endDate) {
                $conditions[] = 'date <= %s';
                $params[] = $endDate->format('Y-m-d H:i:s');
            }

            $where = 'WHERE ' . implode(' AND ', $conditions);
            $query = $this->wpdb->prepare(
                "SELECT SUM(amount) FROM {$this->table} {$where}", 
                $params
            );

            return (float) $this->wpdb->get_var($query);
        } catch (Exception $e) {
            $this->logger->error('Erro ao calcular saldo total', [
                'error' => $e->getMessage(),
                'condominium_id' => $condominiumId
            ]);
            throw $e;
        }
    }
}
