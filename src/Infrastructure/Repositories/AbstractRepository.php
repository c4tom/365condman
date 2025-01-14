<?php
namespace CondMan\Infrastructure\Repositories;

use CondMan\Domain\Interfaces\RepositoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Log\LoggerInterface;

abstract class AbstractRepository implements RepositoryInterface {
    protected Connection $connection;
    protected LoggerInterface $logger;
    protected string $tableName;

    public function __construct(Connection $connection, LoggerInterface $logger, string $tableName) {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->tableName = $tableName;
    }

    public function findById(int $id): ?array {
        try {
            $queryBuilder = $this->connection->createQueryBuilder();
            $result = $queryBuilder
                ->select('*')
                ->from($this->tableName)
                ->where('id = :id')
                ->setParameter('id', $id)
                ->executeQuery()
                ->fetchAssociative();

            return $result ?: null;
        } catch (\Exception $e) {
            $this->logger->error("Error finding record by ID: {$e->getMessage()}");
            return null;
        }
    }

    public function findAll(array $criteria = [], array $orderBy = [], ?int $limit = null, ?int $offset = null): array {
        try {
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder
                ->select('*')
                ->from($this->tableName);

            // Adicionar critérios de filtro
            foreach ($criteria as $field => $value) {
                $queryBuilder
                    ->andWhere("{$field} = :{$field}")
                    ->setParameter($field, $value);
            }

            // Adicionar ordenação
            foreach ($orderBy as $field => $direction) {
                $queryBuilder->addOrderBy($field, $direction);
            }

            // Adicionar limite e offset
            if ($limit !== null) {
                $queryBuilder->setMaxResults($limit);
            }
            if ($offset !== null) {
                $queryBuilder->setFirstResult($offset);
            }

            return $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (\Exception $e) {
            $this->logger->error("Error finding records: {$e->getMessage()}");
            return [];
        }
    }

    public function count(array $criteria = []): int {
        try {
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder
                ->select('COUNT(*) as total')
                ->from($this->tableName);

            // Adicionar critérios de filtro
            foreach ($criteria as $field => $value) {
                $queryBuilder
                    ->andWhere("{$field} = :{$field}")
                    ->setParameter($field, $value);
            }

            $result = $queryBuilder->executeQuery()->fetchAssociative();
            return (int) $result['total'];
        } catch (\Exception $e) {
            $this->logger->error("Error counting records: {$e->getMessage()}");
            return 0;
        }
    }

    public function insert(array $data): int {
        try {
            $this->connection->insert($this->tableName, $data);
            return (int) $this->connection->lastInsertId();
        } catch (\Exception $e) {
            $this->logger->error("Error inserting record: {$e->getMessage()}");
            throw $e;
        }
    }

    public function update(int $id, array $data): bool {
        try {
            $affectedRows = $this->connection->update(
                $this->tableName, 
                $data, 
                ['id' => $id]
            );
            return $affectedRows > 0;
        } catch (\Exception $e) {
            $this->logger->error("Error updating record: {$e->getMessage()}");
            return false;
        }
    }

    public function delete(int $id): bool {
        try {
            $affectedRows = $this->connection->delete(
                $this->tableName, 
                ['id' => $id]
            );
            return $affectedRows > 0;
        } catch (\Exception $e) {
            $this->logger->error("Error deleting record: {$e->getMessage()}");
            return false;
        }
    }

    public function beginTransaction(): void {
        $this->connection->beginTransaction();
    }

    public function commit(): void {
        $this->connection->commit();
    }

    public function rollback(): void {
        $this->connection->rollBack();
    }

    /**
     * Cria um construtor de consultas
     * @return QueryBuilder Construtor de consultas
     */
    protected function createQueryBuilder(): QueryBuilder {
        return $this->connection->createQueryBuilder();
    }
}
