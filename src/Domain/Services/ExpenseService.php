<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Entities\Expense;
use CondMan\Domain\Interfaces\LoggerInterface;
use CondMan\Infrastructure\Repositories\ExpenseRepository;
use CondMan\Domain\Validators\ExpenseValidator;
use DateTime;

class ExpenseService {
    private ExpenseRepository $expenseRepository;
    private LoggerInterface $logger;
    private ExpenseValidator $validator;

    public function __construct(
        ExpenseRepository $expenseRepository,
        LoggerInterface $logger,
        ExpenseValidator $validator
    ) {
        $this->expenseRepository = $expenseRepository;
        $this->logger = $logger;
        $this->validator = $validator;
    }

    /**
     * Cria uma nova despesa
     * @param array $data Dados da despesa
     * @return Expense Despesa criada
     * @throws \InvalidArgumentException Se dados inválidos
     */
    public function createExpense(array $data): Expense {
        try {
            // Valida dados da despesa
            if (!$this->validator->validate($data)) {
                $errors = $this->validator->getErrors();
                throw new \InvalidArgumentException(json_encode($errors));
            }

            // Cria entidade de despesa
            $expense = new Expense($data);

            // Inicia transação
            $this->expenseRepository->beginTransaction();

            // Insere despesa
            $expenseId = $this->expenseRepository->insert($expense);
            $expense->setId($expenseId);

            // Confirma transação
            $this->expenseRepository->commit();

            $this->logger->info('Expense created', [
                'expense_id' => $expenseId,
                'condominium_id' => $expense->getCondominiumId()
            ]);

            return $expense;
        } catch (\Exception $e) {
            // Reverte transação em caso de erro
            $this->expenseRepository->rollback();

            $this->logger->error('Error creating expense', [
                'exception' => $e->getMessage(),
                'data' => $data
            ]);

            throw $e;
        }
    }

    /**
     * Atualiza uma despesa existente
     * @param int $expenseId ID da despesa
     * @param array $data Dados atualizados
     * @return Expense Despesa atualizada
     * @throws \InvalidArgumentException Se dados inválidos
     */
    public function updateExpense(int $expenseId, array $data): Expense {
        try {
            // Busca despesa existente
            $expense = $this->expenseRepository->findById($expenseId);
            if (!$expense) {
                throw new \InvalidArgumentException("Expense not found");
            }

            // Valida dados da despesa
            if (!$this->validator->validate($data)) {
                $errors = $this->validator->getErrors();
                throw new \InvalidArgumentException(json_encode($errors));
            }

            // Inicia transação
            $this->expenseRepository->beginTransaction();

            // Atualiza despesa
            $this->expenseRepository->update($expenseId, $data);

            // Recarrega despesa atualizada
            $updatedExpense = $this->expenseRepository->findById($expenseId);

            // Confirma transação
            $this->expenseRepository->commit();

            $this->logger->info('Expense updated', [
                'expense_id' => $expenseId,
                'condominium_id' => $updatedExpense->getCondominiumId()
            ]);

            return $updatedExpense;
        } catch (\Exception $e) {
            // Reverte transação em caso de erro
            $this->expenseRepository->rollback();

            $this->logger->error('Error updating expense', [
                'exception' => $e->getMessage(),
                'expense_id' => $expenseId,
                'data' => $data
            ]);

            throw $e;
        }
    }

    /**
     * Aprova uma despesa
     * @param int $expenseId ID da despesa
     * @param string $approvedBy Usuário que aprovou
     * @return Expense Despesa aprovada
     */
    public function approveExpense(int $expenseId, string $approvedBy): Expense {
        try {
            // Inicia transação
            $this->expenseRepository->beginTransaction();

            // Atualiza status da despesa
            $this->expenseRepository->update($expenseId, [
                'approved' => true,
                'approved_by' => $approvedBy
            ]);

            // Recarrega despesa atualizada
            $expense = $this->expenseRepository->findById($expenseId);

            // Confirma transação
            $this->expenseRepository->commit();

            $this->logger->info('Expense approved', [
                'expense_id' => $expenseId,
                'approved_by' => $approvedBy
            ]);

            return $expense;
        } catch (\Exception $e) {
            // Reverte transação em caso de erro
            $this->expenseRepository->rollback();

            $this->logger->error('Error approving expense', [
                'exception' => $e->getMessage(),
                'expense_id' => $expenseId
            ]);

            throw $e;
        }
    }

    /**
     * Registra pagamento de despesa
     * @param int $expenseId ID da despesa
     * @param float $paidAmount Valor pago
     * @param DateTime $paymentDate Data de pagamento
     * @return Expense Despesa com pagamento registrado
     */
    public function registerPayment(
        int $expenseId, 
        float $paidAmount, 
        DateTime $paymentDate
    ): Expense {
        try {
            // Inicia transação
            $this->expenseRepository->beginTransaction();

            // Atualiza status da despesa
            $this->expenseRepository->update($expenseId, [
                'payment_status' => 'paid',
                'payment_date' => $paymentDate->format('Y-m-d H:i:s'),
                'total_paid' => $paidAmount
            ]);

            // Recarrega despesa atualizada
            $expense = $this->expenseRepository->findById($expenseId);

            // Confirma transação
            $this->expenseRepository->commit();

            $this->logger->info('Expense payment registered', [
                'expense_id' => $expenseId,
                'paid_amount' => $paidAmount
            ]);

            return $expense;
        } catch (\Exception $e) {
            // Reverte transação em caso de erro
            $this->expenseRepository->rollback();

            $this->logger->error('Error registering expense payment', [
                'exception' => $e->getMessage(),
                'expense_id' => $expenseId
            ]);

            throw $e;
        }
    }

    /**
     * Busca despesas por condomínio
     * @param int $condominiumId ID do condomínio
     * @param array $filters Filtros adicionais
     * @return array Lista de despesas
     */
    public function findExpensesByCondominium(
        int $condominiumId, 
        array $filters = []
    ): array {
        try {
            $filters['condominium_id'] = $condominiumId;
            return $this->expenseRepository->findAll($filters);
        } catch (\Exception $e) {
            $this->logger->error('Error finding expenses', [
                'exception' => $e->getMessage(),
                'condominium_id' => $condominiumId,
                'filters' => $filters
            ]);

            throw $e;
        }
    }
}
