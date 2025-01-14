<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\PersistenceServiceInterface;
use CondMan\Domain\Interfaces\ValidatorInterface;
use CondMan\Domain\Interfaces\TransformerInterface;
use CondMan\Infrastructure\Repositories\AbstractRepository;
use Psr\Log\LoggerInterface;

abstract class AbstractPersistenceService implements PersistenceServiceInterface {
    protected AbstractRepository $repository;
    protected LoggerInterface $logger;
    protected ?ValidatorInterface $validator;
    protected ?TransformerInterface $transformer;

    public function __construct(
        AbstractRepository $repository, 
        LoggerInterface $logger, 
        ?ValidatorInterface $validator = null, 
        ?TransformerInterface $transformer = null
    ) {
        $this->repository = $repository;
        $this->logger = $logger;
        $this->validator = $validator;
        $this->transformer = $transformer;
    }

    /**
     * Valida dados antes da persistência
     * @param array $data Dados a serem validados
     * @throws \InvalidArgumentException Se dados inválidos
     */
    protected function validateData(array $data): void {
        if ($this->validator) {
            $validationResult = $this->validator->validate($data);
            if (!$validationResult->isValid()) {
                $errors = $validationResult->getErrors();
                $this->logger->error('Validation failed', ['errors' => $errors]);
                throw new \InvalidArgumentException(json_encode($errors));
            }
        }
    }

    /**
     * Transforma dados antes da persistência
     * @param array $data Dados a serem transformados
     * @return array Dados transformados
     */
    protected function transformData(array $data): array {
        if ($this->transformer) {
            return $this->transformer->transform($data);
        }
        return $data;
    }

    public function save(array $data): int {
        try {
            $this->validateData($data);
            $transformedData = $this->transformData($data);
            return $this->repository->insert($transformedData);
        } catch (\Exception $e) {
            $this->logger->error('Error saving entity', [
                'exception' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    public function update(int $id, array $data): bool {
        try {
            $this->validateData($data);
            $transformedData = $this->transformData($data);
            return $this->repository->update($id, $transformedData);
        } catch (\Exception $e) {
            $this->logger->error('Error updating entity', [
                'exception' => $e->getMessage(),
                'id' => $id,
                'data' => $data
            ]);
            throw $e;
        }
    }

    public function delete(int $id): bool {
        try {
            return $this->repository->delete($id);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting entity', [
                'exception' => $e->getMessage(),
                'id' => $id
            ]);
            throw $e;
        }
    }

    public function findById(int $id): ?array {
        try {
            return $this->repository->findById($id);
        } catch (\Exception $e) {
            $this->logger->error('Error finding entity by ID', [
                'exception' => $e->getMessage(),
                'id' => $id
            ]);
            throw $e;
        }
    }

    public function findAll(array $criteria = [], array $orderBy = [], ?int $limit = null, ?int $offset = null): array {
        try {
            return $this->repository->findAll($criteria, $orderBy, $limit, $offset);
        } catch (\Exception $e) {
            $this->logger->error('Error finding entities', [
                'exception' => $e->getMessage(),
                'criteria' => $criteria,
                'orderBy' => $orderBy,
                'limit' => $limit,
                'offset' => $offset
            ]);
            throw $e;
        }
    }

    public function count(array $criteria = []): int {
        try {
            return $this->repository->count($criteria);
        } catch (\Exception $e) {
            $this->logger->error('Error counting entities', [
                'exception' => $e->getMessage(),
                'criteria' => $criteria
            ]);
            throw $e;
        }
    }

    /**
     * Inicia uma transação
     */
    public function beginTransaction(): void {
        $this->repository->beginTransaction();
    }

    /**
     * Confirma a transação atual
     */
    public function commit(): void {
        $this->repository->commit();
    }

    /**
     * Reverte a transação atual
     */
    public function rollback(): void {
        $this->repository->rollback();
    }
}
