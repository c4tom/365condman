<?php
namespace CondMan\Infrastructure\Repositories;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class InvoiceRepository extends AbstractRepository {
    private string $invoiceItemTableName;
    private string $paymentTableName;

    public function __construct(Connection $connection, LoggerInterface $logger) {
        parent::__construct($connection, $logger, 'wp_condman_invoices');
        $this->invoiceItemTableName = 'wp_condman_invoice_items';
        $this->paymentTableName = 'wp_condman_payments';
    }

    /**
     * Encontra faturas por condomínio
     * @param int $condominiumId ID do condomínio
     * @param array $filters Filtros adicionais
     * @return array Lista de faturas
     */
    public function findByCondominium(int $condominiumId, array $filters = []): array {
        try {
            $queryBuilder = $this->createQueryBuilder();
            $queryBuilder
                ->select('*')
                ->from($this->tableName)
                ->where('condominium_id = :condominiumId')
                ->setParameter('condominiumId', $condominiumId);

            // Filtros adicionais
            if (isset($filters['status'])) {
                $queryBuilder
                    ->andWhere('status = :status')
                    ->setParameter('status', $filters['status']);
            }

            if (isset($filters['referenceMonth'])) {
                $queryBuilder
                    ->andWhere('reference_month = :referenceMonth')
                    ->setParameter('referenceMonth', $filters['referenceMonth']);
            }

            if (isset($filters['referenceYear'])) {
                $queryBuilder
                    ->andWhere('reference_year = :referenceYear')
                    ->setParameter('referenceYear', $filters['referenceYear']);
            }

            return $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (\Exception $e) {
            $this->logger->error("Error finding invoices by condominium: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Encontra faturas por unidade
     * @param int $unitId ID da unidade
     * @param array $filters Filtros adicionais
     * @return array Lista de faturas
     */
    public function findByUnit(int $unitId, array $filters = []): array {
        try {
            $queryBuilder = $this->createQueryBuilder();
            $queryBuilder
                ->select('*')
                ->from($this->tableName)
                ->where('unit_id = :unitId')
                ->setParameter('unitId', $unitId);

            // Filtros adicionais
            if (isset($filters['status'])) {
                $queryBuilder
                    ->andWhere('status = :status')
                    ->setParameter('status', $filters['status']);
            }

            return $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (\Exception $e) {
            $this->logger->error("Error finding invoices by unit: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Adiciona um item à fatura
     * @param int $invoiceId ID da fatura
     * @param array $itemData Dados do item
     * @return int ID do item adicionado
     */
    public function addInvoiceItem(int $invoiceId, array $itemData): int {
        try {
            $itemData['invoice_id'] = $invoiceId;
            return $this->connection->insert($this->invoiceItemTableName, $itemData) 
                ? (int) $this->connection->lastInsertId() 
                : 0;
        } catch (\Exception $e) {
            $this->logger->error("Error adding invoice item: {$e->getMessage()}");
            return 0;
        }
    }

    /**
     * Encontra itens de uma fatura
     * @param int $invoiceId ID da fatura
     * @return array Lista de itens da fatura
     */
    public function findInvoiceItems(int $invoiceId): array {
        try {
            $queryBuilder = $this->createQueryBuilder();
            $result = $queryBuilder
                ->select('*')
                ->from($this->invoiceItemTableName)
                ->where('invoice_id = :invoiceId')
                ->setParameter('invoiceId', $invoiceId)
                ->executeQuery()
                ->fetchAllAssociative();

            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Error finding invoice items: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Registra um pagamento para a fatura
     * @param int $invoiceId ID da fatura
     * @param array $paymentData Dados do pagamento
     * @return int ID do pagamento registrado
     */
    public function registerPayment(int $invoiceId, array $paymentData): int {
        try {
            $paymentData['invoice_id'] = $invoiceId;
            $paymentData['payment_date'] = new \DateTime();

            $this->connection->beginTransaction();

            // Registra o pagamento
            $this->connection->insert($this->paymentTableName, $paymentData);
            $paymentId = (int) $this->connection->lastInsertId();

            // Atualiza o status e valor pago da fatura
            $this->connection->update(
                $this->tableName, 
                [
                    'total_paid' => $this->calculateTotalPaid($invoiceId, $paymentData['amount']),
                    'status' => $this->determineInvoiceStatus($invoiceId),
                    'payment_date' => new \DateTime(),
                    'updated_at' => new \DateTime()
                ],
                ['id' => $invoiceId]
            );

            $this->connection->commit();

            return $paymentId;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            $this->logger->error("Error registering payment: {$e->getMessage()}");
            return 0;
        }
    }

    /**
     * Calcula o total pago de uma fatura
     * @param int $invoiceId ID da fatura
     * @param float $newPaymentAmount Novo valor de pagamento
     * @return float Total pago
     */
    private function calculateTotalPaid(int $invoiceId, float $newPaymentAmount): float {
        $queryBuilder = $this->createQueryBuilder();
        $currentTotalPaid = $queryBuilder
            ->select('total_paid')
            ->from($this->tableName)
            ->where('id = :invoiceId')
            ->setParameter('invoiceId', $invoiceId)
            ->executeQuery()
            ->fetchOne();

        return (float) $currentTotalPaid + $newPaymentAmount;
    }

    /**
     * Determina o status da fatura com base nos pagamentos
     * @param int $invoiceId ID da fatura
     * @return string Status da fatura
     */
    private function determineInvoiceStatus(int $invoiceId): string {
        $queryBuilder = $this->createQueryBuilder();
        $totalAmount = $queryBuilder
            ->select('total_amount')
            ->from($this->tableName)
            ->where('id = :invoiceId')
            ->setParameter('invoiceId', $invoiceId)
            ->executeQuery()
            ->fetchOne();

        $totalPaid = $this->calculateTotalPaid($invoiceId, 0);

        if ($totalPaid >= $totalAmount) {
            return 'PAID';
        } elseif ($totalPaid > 0) {
            return 'PARTIALLY_PAID';
        }

        return 'PENDING';
    }

    /**
     * Encontra pagamentos de uma fatura
     * @param int $invoiceId ID da fatura
     * @return array Lista de pagamentos
     */
    public function findPayments(int $invoiceId): array {
        try {
            $queryBuilder = $this->createQueryBuilder();
            $result = $queryBuilder
                ->select('*')
                ->from($this->paymentTableName)
                ->where('invoice_id = :invoiceId')
                ->setParameter('invoiceId', $invoiceId)
                ->executeQuery()
                ->fetchAllAssociative();

            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Error finding invoice payments: {$e->getMessage()}");
            return [];
        }
    }
}
