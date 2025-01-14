<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\MigrationInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;

class MigrationService {
    private Connection $connection;
    private LoggerInterface $logger;
    private string $migrationTableName;

    public function __construct(Connection $connection, LoggerInterface $logger) {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->migrationTableName = 'wp_condman_migrations';
    }

    /**
     * Executa uma migração
     * @param MigrationInterface $migration Migração a ser executada
     * @return bool Indica se a migração foi bem-sucedida
     */
    public function runMigration(MigrationInterface $migration): bool {
        if ($migration->isApplied()) {
            $this->logger->info("Migration {$migration->getVersion()} already applied.");
            return true;
        }

        try {
            $result = $migration->up();
            
            if ($result) {
                $this->recordMigration($migration);
                $this->logger->info("Migration {$migration->getVersion()} successfully applied.");
            } else {
                $this->logger->error("Migration {$migration->getVersion()} failed.");
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Migration error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reverte uma migração
     * @param MigrationInterface $migration Migração a ser revertida
     * @return bool Indica se o rollback foi bem-sucedido
     */
    public function rollbackMigration(MigrationInterface $migration): bool {
        if (!$migration->isApplied()) {
            $this->logger->info("Migration {$migration->getVersion()} not applied.");
            return true;
        }

        try {
            $result = $migration->down();
            
            if ($result) {
                $this->removeMigrationRecord($migration);
                $this->logger->info("Migration {$migration->getVersion()} successfully rolled back.");
            } else {
                $this->logger->error("Rollback of migration {$migration->getVersion()} failed.");
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Rollback error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra uma migração como aplicada
     * @param MigrationInterface $migration Migração a ser registrada
     */
    private function recordMigration(MigrationInterface $migration): void {
        $this->ensureMigrationTableExists();

        $this->connection->insert($this->migrationTableName, [
            'version' => $migration->getVersion(),
            'description' => $migration->getDescription(),
            'applied_at' => new \DateTime()
        ]);
    }

    /**
     * Remove o registro de uma migração
     * @param MigrationInterface $migration Migração a ser removida
     */
    private function removeMigrationRecord(MigrationInterface $migration): void {
        $this->connection->delete($this->migrationTableName, [
            'version' => $migration->getVersion()
        ]);
    }

    /**
     * Garante que a tabela de migrações exista
     */
    private function ensureMigrationTableExists(): void {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([$this->migrationTableName])) {
            $schema = new Schema();
            $migrationTable = $schema->createTable($this->migrationTableName);
            
            $migrationTable->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
            $migrationTable->addColumn('version', Types::STRING, ['length' => 50]);
            $migrationTable->addColumn('description', Types::TEXT, ['notnull' => false]);
            $migrationTable->addColumn('applied_at', Types::DATETIME_MUTABLE);
            
            $migrationTable->setPrimaryKey(['id']);
            $migrationTable->addUniqueIndex(['version']);

            $this->connection->executeStatement($schema->toSql($this->connection->getDatabasePlatform())[0]);
        }
    }

    /**
     * Lista todas as migrações aplicadas
     * @return array Lista de migrações aplicadas
     */
    public function listAppliedMigrations(): array {
        $this->ensureMigrationTableExists();

        $queryBuilder = $this->connection->createQueryBuilder();
        $result = $queryBuilder
            ->select('version', 'description', 'applied_at')
            ->from($this->migrationTableName)
            ->orderBy('applied_at', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return $result;
    }
}
