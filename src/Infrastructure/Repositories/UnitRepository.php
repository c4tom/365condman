<?php
namespace CondMan\Infrastructure\Repositories;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class UnitRepository extends AbstractRepository {
    public function __construct(Connection $connection, LoggerInterface $logger) {
        parent::__construct($connection, $logger, 'wp_condman_units');
    }

    /**
     * Encontra unidades por condomínio
     * @param int $condominiumId ID do condomínio
     * @return array Lista de unidades
     */
    public function findByCondominium(int $condominiumId): array {
        try {
            $queryBuilder = $this->createQueryBuilder();
            $result = $queryBuilder
                ->select('*')
                ->from($this->tableName)
                ->where('condominium_id = :condominiumId')
                ->setParameter('condominiumId', $condominiumId)
                ->executeQuery()
                ->fetchAllAssociative();

            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Error finding units by condominium: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Encontra unidades por bloco
     * @param int $condominiumId ID do condomínio
     * @param string $block Bloco da unidade
     * @return array Lista de unidades
     */
    public function findByBlock(int $condominiumId, string $block): array {
        try {
            $queryBuilder = $this->createQueryBuilder();
            $result = $queryBuilder
                ->select('*')
                ->from($this->tableName)
                ->where('condominium_id = :condominiumId')
                ->andWhere('block = :block')
                ->setParameter('condominiumId', $condominiumId)
                ->setParameter('block', $block)
                ->executeQuery()
                ->fetchAllAssociative();

            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Error finding units by block: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Encontra unidades por status
     * @param int $condominiumId ID do condomínio
     * @param string $status Status da unidade
     * @return array Lista de unidades
     */
    public function findByStatus(int $condominiumId, string $status): array {
        try {
            $queryBuilder = $this->createQueryBuilder();
            $result = $queryBuilder
                ->select('*')
                ->from($this->tableName)
                ->where('condominium_id = :condominiumId')
                ->andWhere('status = :status')
                ->setParameter('condominiumId', $condominiumId)
                ->setParameter('status', $status)
                ->executeQuery()
                ->fetchAllAssociative();

            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Error finding units by status: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Encontra unidades por tipo
     * @param int $condominiumId ID do condomínio
     * @param string $type Tipo da unidade
     * @return array Lista de unidades
     */
    public function findByType(int $condominiumId, string $type): array {
        try {
            $queryBuilder = $this->createQueryBuilder();
            $result = $queryBuilder
                ->select('*')
                ->from($this->tableName)
                ->where('condominium_id = :condominiumId')
                ->andWhere('type = :type')
                ->setParameter('condominiumId', $condominiumId)
                ->setParameter('type', $type)
                ->executeQuery()
                ->fetchAllAssociative();

            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Error finding units by type: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Atualiza o status de uma unidade
     * @param int $unitId ID da unidade
     * @param string $status Novo status
     * @return bool Indica se a atualização foi bem-sucedida
     */
    public function updateStatus(int $unitId, string $status): bool {
        try {
            $affectedRows = $this->connection->update(
                $this->tableName,
                [
                    'status' => $status,
                    'updated_at' => new \DateTime()
                ],
                ['id' => $unitId]
            );

            return $affectedRows > 0;
        } catch (\Exception $e) {
            $this->logger->error("Error updating unit status: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Calcula o número total de unidades por status em um condomínio
     * @param int $condominiumId ID do condomínio
     * @return array Contagem de unidades por status
     */
    public function countUnitsByStatus(int $condominiumId): array {
        try {
            $queryBuilder = $this->createQueryBuilder();
            $result = $queryBuilder
                ->select('status', 'COUNT(*) as count')
                ->from($this->tableName)
                ->where('condominium_id = :condominiumId')
                ->groupBy('status')
                ->setParameter('condominiumId', $condominiumId)
                ->executeQuery()
                ->fetchAllAssociative();

            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Error counting units by status: {$e->getMessage()}");
            return [];
        }
    }
}
