<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Entities\Revenue;
use CondMan\Domain\Interfaces\LoggerInterface;
use CondMan\Infrastructure\Repositories\RevenueRepository;
use CondMan\Domain\Validators\RevenueValidator;
use DateTime;

class RevenueService {
    private RevenueRepository $revenueRepository;
    private LoggerInterface $logger;
    private RevenueValidator $validator;

    public function __construct(
        RevenueRepository $revenueRepository,
        LoggerInterface $logger,
        RevenueValidator $validator
    ) {
        $this->revenueRepository = $revenueRepository;
        $this->logger = $logger;
        $this->validator = $validator;
    }

    /**
     * Cria uma nova receita
     * @param array $data Dados da receita
     * @return Revenue Receita criada
     * @throws \InvalidArgumentException Se dados inválidos
     */
    public function createRevenue(array $data): Revenue {
        try {
            // Valida dados da receita
            if (!$this->validator->validate($data)) {
                $errors = $this->validator->getErrors();
                throw new \InvalidArgumentException(json_encode($errors));
            }

            // Cria entidade de receita
            $revenue = new Revenue($data);

            // Inicia transação
            $this->revenueRepository->beginTransaction();

            // Insere receita
            $revenueId = $this->revenueRepository->insert($revenue);
            $revenue->setId($revenueId);

            // Confirma transação
            $this->revenueRepository->commit();

            $this->logger->info('Revenue created', [
                'revenue_id' => $revenueId,
                'condominium_id' => $revenue->getCondominiumId()
            ]);

            return $revenue;
        } catch (\Exception $e) {
            // Reverte transação em caso de erro
            $this->revenueRepository->rollback();

            $this->logger->error('Error creating revenue', [
                'exception' => $e->getMessage(),
                'data' => $data
            ]);

            throw $e;
        }
    }

    /**
     * Atualiza uma receita existente
     * @param int $revenueId ID da receita
     * @param array $data Dados atualizados
     * @return Revenue Receita atualizada
     * @throws \InvalidArgumentException Se dados inválidos
     */
    public function updateRevenue(int $revenueId, array $data): Revenue {
        try {
            // Busca receita existente
            $revenue = $this->revenueRepository->findById($revenueId);
            if (!$revenue) {
                throw new \InvalidArgumentException("Revenue not found");
            }

            // Valida dados da receita
            if (!$this->validator->validate($data)) {
                $errors = $this->validator->getErrors();
                throw new \InvalidArgumentException(json_encode($errors));
            }

            // Inicia transação
            $this->revenueRepository->beginTransaction();

            // Atualiza receita
            $this->revenueRepository->update($revenueId, $data);

            // Recarrega receita atualizada
            $updatedRevenue = $this->revenueRepository->findById($revenueId);

            // Confirma transação
            $this->revenueRepository->commit();

            $this->logger->info('Revenue updated', [
                'revenue_id' => $revenueId,
                'condominium_id' => $updatedRevenue->getCondominiumId()
            ]);

            return $updatedRevenue;
        } catch (\Exception $e) {
            // Reverte transação em caso de erro
            $this->revenueRepository->rollback();

            $this->logger->error('Error updating revenue', [
                'exception' => $e->getMessage(),
                'revenue_id' => $revenueId,
                'data' => $data
            ]);

            throw $e;
        }
    }

    /**
     * Confirma uma receita
     * @param int $revenueId ID da receita
     * @return Revenue Receita confirmada
     */
    public function confirmRevenue(int $revenueId): Revenue {
        try {
            // Inicia transação
            $this->revenueRepository->beginTransaction();

            // Atualiza status da receita
            $this->revenueRepository->update($revenueId, [
                'confirmed' => true,
                'receipt_status' => 'confirmed'
            ]);

            // Recarrega receita atualizada
            $revenue = $this->revenueRepository->findById($revenueId);

            // Confirma transação
            $this->revenueRepository->commit();

            $this->logger->info('Revenue confirmed', [
                'revenue_id' => $revenueId
            ]);

            return $revenue;
        } catch (\Exception $e) {
            // Reverte transação em caso de erro
            $this->revenueRepository->rollback();

            $this->logger->error('Error confirming revenue', [
                'exception' => $e->getMessage(),
                'revenue_id' => $revenueId
            ]);

            throw $e;
        }
    }

    /**
     * Registra recebimento de receita
     * @param int $revenueId ID da receita
     * @param float $receivedAmount Valor recebido
     * @param DateTime $receiptDate Data de recebimento
     * @return Revenue Receita com recebimento registrado
     */
    public function registerReceipt(
        int $revenueId, 
        float $receivedAmount, 
        DateTime $receiptDate
    ): Revenue {
        try {
            // Inicia transação
            $this->revenueRepository->beginTransaction();

            // Atualiza status da receita
            $this->revenueRepository->update($revenueId, [
                'receipt_status' => 'received',
                'receipt_date' => $receiptDate->format('Y-m-d H:i:s'),
                'total_amount' => $receivedAmount
            ]);

            // Recarrega receita atualizada
            $revenue = $this->revenueRepository->findById($revenueId);

            // Confirma transação
            $this->revenueRepository->commit();

            $this->logger->info('Revenue receipt registered', [
                'revenue_id' => $revenueId,
                'received_amount' => $receivedAmount
            ]);

            return $revenue;
        } catch (\Exception $e) {
            // Reverte transação em caso de erro
            $this->revenueRepository->rollback();

            $this->logger->error('Error registering revenue receipt', [
                'exception' => $e->getMessage(),
                'revenue_id' => $revenueId
            ]);

            throw $e;
        }
    }

    /**
     * Busca receitas por condomínio
     * @param int $condominiumId ID do condomínio
     * @param array $filters Filtros adicionais
     * @return array Lista de receitas
     */
    public function findRevenuesByCondominium(
        int $condominiumId, 
        array $filters = []
    ): array {
        try {
            $filters['condominium_id'] = $condominiumId;
            return $this->revenueRepository->findAll($filters);
        } catch (\Exception $e) {
            $this->logger->error('Error finding revenues', [
                'exception' => $e->getMessage(),
                'condominium_id' => $condominiumId,
                'filters' => $filters
            ]);

            throw $e;
        }
    }
}
