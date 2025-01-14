<?php
namespace CondMan\Infrastructure\Repositories;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class CommunicationRepository extends AbstractRepository {
    private string $templateTableName;
    private string $logTableName;

    public function __construct(Connection $connection, LoggerInterface $logger) {
        parent::__construct($connection, $logger, 'wp_condman_communications');
        $this->templateTableName = 'wp_condman_communication_templates';
        $this->logTableName = 'wp_condman_communication_logs';
    }

    /**
     * Encontra comunicações por condomínio
     * @param int $condominiumId ID do condomínio
     * @param array $filters Filtros adicionais
     * @return array Lista de comunicações
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
            if (isset($filters['channel'])) {
                $queryBuilder
                    ->andWhere('channel = :channel')
                    ->setParameter('channel', $filters['channel']);
            }

            if (isset($filters['status'])) {
                $queryBuilder
                    ->andWhere('status = :status')
                    ->setParameter('status', $filters['status']);
            }

            return $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (\Exception $e) {
            $this->logger->error("Error finding communications by condominium: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Encontra comunicações por unidade
     * @param int $unitId ID da unidade
     * @param array $filters Filtros adicionais
     * @return array Lista de comunicações
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
            if (isset($filters['channel'])) {
                $queryBuilder
                    ->andWhere('channel = :channel')
                    ->setParameter('channel', $filters['channel']);
            }

            if (isset($filters['status'])) {
                $queryBuilder
                    ->andWhere('status = :status')
                    ->setParameter('status', $filters['status']);
            }

            return $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (\Exception $e) {
            $this->logger->error("Error finding communications by unit: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Cria um template de comunicação
     * @param array $templateData Dados do template
     * @return int ID do template criado
     */
    public function createTemplate(array $templateData): int {
        try {
            $templateData['created_at'] = new \DateTime();
            $templateData['updated_at'] = new \DateTime();
            $templateData['is_active'] = $templateData['is_active'] ?? true;

            return $this->connection->insert($this->templateTableName, $templateData)
                ? (int) $this->connection->lastInsertId()
                : 0;
        } catch (\Exception $e) {
            $this->logger->error("Error creating communication template: {$e->getMessage()}");
            return 0;
        }
    }

    /**
     * Encontra templates de comunicação
     * @param array $filters Filtros para busca de templates
     * @return array Lista de templates
     */
    public function findTemplates(array $filters = []): array {
        try {
            $queryBuilder = $this->createQueryBuilder();
            $queryBuilder
                ->select('*')
                ->from($this->templateTableName);

            // Filtros adicionais
            if (isset($filters['channel'])) {
                $queryBuilder
                    ->andWhere('channel = :channel')
                    ->setParameter('channel', $filters['channel']);
            }

            if (isset($filters['is_active'])) {
                $queryBuilder
                    ->andWhere('is_active = :is_active')
                    ->setParameter('is_active', $filters['is_active']);
            }

            return $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (\Exception $e) {
            $this->logger->error("Error finding communication templates: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Adiciona um log de comunicação
     * @param int $communicationId ID da comunicação
     * @param array $logData Dados do log
     * @return int ID do log criado
     */
    public function addCommunicationLog(int $communicationId, array $logData): int {
        try {
            $logData['communication_id'] = $communicationId;
            $logData['created_at'] = new \DateTime();

            return $this->connection->insert($this->logTableName, $logData)
                ? (int) $this->connection->lastInsertId()
                : 0;
        } catch (\Exception $e) {
            $this->logger->error("Error adding communication log: {$e->getMessage()}");
            return 0;
        }
    }

    /**
     * Encontra logs de uma comunicação
     * @param int $communicationId ID da comunicação
     * @param array $filters Filtros adicionais
     * @return array Lista de logs
     */
    public function findCommunicationLogs(int $communicationId, array $filters = []): array {
        try {
            $queryBuilder = $this->createQueryBuilder();
            $queryBuilder
                ->select('*')
                ->from($this->logTableName)
                ->where('communication_id = :communicationId')
                ->setParameter('communicationId', $communicationId);

            // Filtros adicionais
            if (isset($filters['event_type'])) {
                $queryBuilder
                    ->andWhere('event_type = :event_type')
                    ->setParameter('event_type', $filters['event_type']);
            }

            return $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (\Exception $e) {
            $this->logger->error("Error finding communication logs: {$e->getMessage()}");
            return [];
        }
    }
}
