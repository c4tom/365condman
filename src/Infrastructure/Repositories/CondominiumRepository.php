<?php
namespace CondMan\Infrastructure\Repositories;

use CondMan\Domain\Interfaces\CondominiumInterface;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class CondominiumRepository extends AbstractRepository {
    public function __construct(Connection $connection, LoggerInterface $logger) {
        parent::__construct($connection, $logger, 'wp_condman_condominiums');
    }

    /**
     * Encontra condomínios por CNPJ
     * @param string $cnpj CNPJ do condomínio
     * @return array|null Dados do condomínio
     */
    public function findByCNPJ(string $cnpj): ?array {
        try {
            $queryBuilder = $this->createQueryBuilder();
            $result = $queryBuilder
                ->select('*')
                ->from($this->tableName)
                ->where('cnpj = :cnpj')
                ->setParameter('cnpj', $cnpj)
                ->executeQuery()
                ->fetchAssociative();

            return $result ?: null;
        } catch (\Exception $e) {
            $this->logger->error("Error finding condominium by CNPJ: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Encontra condomínios por nome
     * @param string $name Nome do condomínio
     * @param bool $partial Busca parcial
     * @return array Lista de condomínios
     */
    public function findByName(string $name, bool $partial = false): array {
        try {
            $queryBuilder = $this->createQueryBuilder();
            $queryBuilder
                ->select('*')
                ->from($this->tableName);

            if ($partial) {
                $queryBuilder
                    ->where('name LIKE :name')
                    ->setParameter('name', "%{$name}%");
            } else {
                $queryBuilder
                    ->where('name = :name')
                    ->setParameter('name', $name);
            }

            return $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (\Exception $e) {
            $this->logger->error("Error finding condominiums by name: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Encontra condomínios com número de unidades maior que o especificado
     * @param int $minUnits Número mínimo de unidades
     * @return array Lista de condomínios
     */
    public function findByMinUnits(int $minUnits): array {
        try {
            $queryBuilder = $this->createQueryBuilder();
            $result = $queryBuilder
                ->select('*')
                ->from($this->tableName)
                ->where('total_units >= :minUnits')
                ->setParameter('minUnits', $minUnits)
                ->executeQuery()
                ->fetchAllAssociative();

            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Error finding condominiums by minimum units: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Atualiza o número total e ocupado de unidades
     * @param int $condominiumId ID do condomínio
     * @param int $totalUnits Número total de unidades
     * @param int $occupiedUnits Número de unidades ocupadas
     * @return bool Indica se a atualização foi bem-sucedida
     */
    public function updateUnitCount(int $condominiumId, int $totalUnits, int $occupiedUnits): bool {
        try {
            $affectedRows = $this->connection->update(
                $this->tableName,
                [
                    'total_units' => $totalUnits,
                    'occupied_units' => $occupiedUnits,
                    'updated_at' => new \DateTime()
                ],
                ['id' => $condominiumId]
            );

            return $affectedRows > 0;
        } catch (\Exception $e) {
            $this->logger->error("Error updating condominium unit count: {$e->getMessage()}");
            return false;
        }
    }
}
