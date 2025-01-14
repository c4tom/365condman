<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\IntegrationInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use CondMan\Infrastructure\Repositories\InvoiceRepository;
use CondMan\Infrastructure\Repositories\CondominiumRepository;

class FinancialIntegrationService implements IntegrationInterface {
    private InvoiceRepository $invoiceRepository;
    private CondominiumRepository $condominiumRepository;
    private LoggerInterface $logger;

    public function __construct(
        InvoiceRepository $invoiceRepository,
        CondominiumRepository $condominiumRepository,
        LoggerInterface $logger
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->condominiumRepository = $condominiumRepository;
        $this->logger = $logger;
    }

    public function validate(array $data): bool {
        // Validações específicas para integração financeira
        $requiredFields = [
            'condominium_id', 
            'total_amount', 
            'due_date', 
            'invoice_items'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->logger->warning("Missing required field for financial integration", [
                    'field' => $field,
                    'data' => $data
                ]);
                return false;
            }
        }

        // Verifica se o condomínio existe
        $condominium = $this->condominiumRepository->findById($data['condominium_id']);
        if (!$condominium) {
            $this->logger->warning("Condominium not found for financial integration", [
                'condominium_id' => $data['condominium_id']
            ]);
            return false;
        }

        // Valida itens da fatura
        if (!$this->validateInvoiceItems($data['invoice_items'])) {
            return false;
        }

        return true;
    }

    private function validateInvoiceItems(array $items): bool {
        foreach ($items as $item) {
            if (!isset($item['description']) || !isset($item['amount']) || $item['amount'] <= 0) {
                $this->logger->warning("Invalid invoice item", ['item' => $item]);
                return false;
            }
        }
        return true;
    }

    public function integrate(array $data): bool {
        try {
            // Valida dados antes da integração
            if (!$this->validate($data)) {
                return false;
            }

            // Inicia transação
            $this->invoiceRepository->beginTransaction();

            // Salva fatura
            $invoiceId = $this->invoiceRepository->insert([
                'condominium_id' => $data['condominium_id'],
                'total_amount' => $data['total_amount'],
                'due_date' => $data['due_date'],
                'status' => 'PENDING'
            ]);

            // Adiciona itens da fatura
            foreach ($data['invoice_items'] as $item) {
                $this->invoiceRepository->addInvoiceItem($invoiceId, [
                    'description' => $item['description'],
                    'amount' => $item['amount']
                ]);
            }

            // Confirma transação
            $this->invoiceRepository->commit();

            $this->logger->info('Financial integration successful', [
                'invoice_id' => $invoiceId,
                'condominium_id' => $data['condominium_id']
            ]);

            return true;
        } catch (\Exception $e) {
            // Reverte transação em caso de erro
            $this->invoiceRepository->rollback();

            $this->logger->error('Financial integration failed', [
                'exception' => $e->getMessage(),
                'data' => $data
            ]);

            return false;
        }
    }

    /**
     * Busca faturas pendentes para integração
     * @param int $condominiumId ID do condomínio
     * @return array Faturas pendentes
     */
    public function findPendingInvoices(int $condominiumId): array {
        return $this->invoiceRepository->findByCondominium($condominiumId, [
            'status' => 'PENDING'
        ]);
    }

    /**
     * Marca fatura como integrada
     * @param int $invoiceId ID da fatura
     * @return bool Indica se a atualização foi bem-sucedida
     */
    public function markAsIntegrated(int $invoiceId): bool {
        try {
            $result = $this->invoiceRepository->update($invoiceId, [
                'status' => 'INTEGRATED'
            ]);

            $this->logger->info('Invoice marked as integrated', [
                'invoice_id' => $invoiceId
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error marking invoice as integrated', [
                'exception' => $e->getMessage(),
                'invoice_id' => $invoiceId
            ]);

            return false;
        }
    }
}
