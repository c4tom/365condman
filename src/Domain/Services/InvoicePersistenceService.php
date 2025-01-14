<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\ValidatorInterface;
use CondMan\Domain\Interfaces\TransformerInterface;
use CondMan\Infrastructure\Repositories\InvoiceRepository;
use CondMan\Infrastructure\Repositories\UnitRepository;
use CondMan\Infrastructure\Repositories\CondominiumRepository;
use Psr\Log\LoggerInterface;

class InvoicePersistenceService extends AbstractPersistenceService {
    private UnitRepository $unitRepository;
    private CondominiumRepository $condominiumRepository;

    public function __construct(
        InvoiceRepository $repository, 
        UnitRepository $unitRepository,
        CondominiumRepository $condominiumRepository,
        LoggerInterface $logger, 
        ?ValidatorInterface $validator = null, 
        ?TransformerInterface $transformer = null
    ) {
        parent::__construct($repository, $logger, $validator, $transformer);
        $this->unitRepository = $unitRepository;
        $this->condominiumRepository = $condominiumRepository;
    }

    /**
     * Salva uma nova fatura com itens
     * @param array $data Dados da fatura
     * @param array $items Itens da fatura
     * @return int ID da fatura salva
     * @throws \Exception Erro durante a persistência
     */
    public function saveWithItems(array $data, array $items): int {
        try {
            $this->validateData($data);
            $transformedData = $this->transformData($data);

            // Inicia transação
            $this->beginTransaction();

            // Salva a fatura
            $invoiceId = $this->repository->insert($transformedData);

            // Adiciona itens à fatura
            /** @var InvoiceRepository $repository */
            $repository = $this->repository;
            foreach ($items as $item) {
                $repository->addInvoiceItem($invoiceId, $item);
            }

            // Confirma transação
            $this->commit();

            return $invoiceId;
        } catch (\Exception $e) {
            // Reverte transação em caso de erro
            $this->rollback();

            $this->logger->error('Error saving invoice with items', [
                'exception' => $e->getMessage(),
                'data' => $data,
                'items' => $items
            ]);
            throw $e;
        }
    }

    /**
     * Registra um pagamento para a fatura
     * @param int $invoiceId ID da fatura
     * @param array $paymentData Dados do pagamento
     * @return int ID do pagamento registrado
     * @throws \Exception Erro durante o registro do pagamento
     */
    public function registerPayment(int $invoiceId, array $paymentData): int {
        try {
            // Inicia transação
            $this->beginTransaction();

            // Registra o pagamento
            /** @var InvoiceRepository $repository */
            $repository = $this->repository;
            $paymentId = $repository->registerPayment($invoiceId, $paymentData);

            // Confirma transação
            $this->commit();

            return $paymentId;
        } catch (\Exception $e) {
            // Reverte transação em caso de erro
            $this->rollback();

            $this->logger->error('Error registering invoice payment', [
                'exception' => $e->getMessage(),
                'invoiceId' => $invoiceId,
                'paymentData' => $paymentData
            ]);
            throw $e;
        }
    }

    /**
     * Encontra faturas por condomínio
     * @param int $condominiumId ID do condomínio
     * @param array $filters Filtros adicionais
     * @return array Lista de faturas
     */
    public function findByCondominium(int $condominiumId, array $filters = []): array {
        try {
            /** @var InvoiceRepository $repository */
            $repository = $this->repository;
            return $repository->findByCondominium($condominiumId, $filters);
        } catch (\Exception $e) {
            $this->logger->error('Error finding invoices by condominium', [
                'exception' => $e->getMessage(),
                'condominiumId' => $condominiumId,
                'filters' => $filters
            ]);
            throw $e;
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
            /** @var InvoiceRepository $repository */
            $repository = $this->repository;
            return $repository->findByUnit($unitId, $filters);
        } catch (\Exception $e) {
            $this->logger->error('Error finding invoices by unit', [
                'exception' => $e->getMessage(),
                'unitId' => $unitId,
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    /**
     * Encontra itens de uma fatura
     * @param int $invoiceId ID da fatura
     * @return array Lista de itens da fatura
     */
    public function findInvoiceItems(int $invoiceId): array {
        try {
            /** @var InvoiceRepository $repository */
            $repository = $this->repository;
            return $repository->findInvoiceItems($invoiceId);
        } catch (\Exception $e) {
            $this->logger->error('Error finding invoice items', [
                'exception' => $e->getMessage(),
                'invoiceId' => $invoiceId
            ]);
            throw $e;
        }
    }

    /**
     * Encontra pagamentos de uma fatura
     * @param int $invoiceId ID da fatura
     * @return array Lista de pagamentos
     */
    public function findPayments(int $invoiceId): array {
        try {
            /** @var InvoiceRepository $repository */
            $repository = $this->repository;
            return $repository->findPayments($invoiceId);
        } catch (\Exception $e) {
            $this->logger->error('Error finding invoice payments', [
                'exception' => $e->getMessage(),
                'invoiceId' => $invoiceId
            ]);
            throw $e;
        }
    }
}
