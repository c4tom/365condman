<?php
namespace CondMan\Infrastructure\Migrations;

use CondMan\Domain\Interfaces\MigrationInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;

class CreateCondominiumTableMigration implements MigrationInterface {
    private Connection $connection;
    private LoggerInterface $logger;
    private string $tableName;

    public function __construct(Connection $connection, LoggerInterface $logger) {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->tableName = 'wp_condman_condominiums';
    }

    public function up(): bool {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist([$this->tableName])) {
            $this->logger->info("Table {$this->tableName} already exists.");
            return true;
        }

        $schema = new Schema();
        $condominiumTable = $schema->createTable($this->tableName);

        $condominiumTable->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $condominiumTable->addColumn('name', Types::STRING, ['length' => 255]);
        $condominiumTable->addColumn('cnpj', Types::STRING, ['length' => 18, 'unique' => true]);
        $condominiumTable->addColumn('address', Types::TEXT);
        $condominiumTable->addColumn('total_units', Types::INTEGER, ['default' => 0]);
        $condominiumTable->addColumn('occupied_units', Types::INTEGER, ['default' => 0]);
        $condominiumTable->addColumn('created_at', Types::DATETIME_MUTABLE);
        $condominiumTable->addColumn('updated_at', Types::DATETIME_MUTABLE);

        $condominiumTable->setPrimaryKey(['id']);
        $condominiumTable->addUniqueIndex(['cnpj']);

        try {
            $this->connection->executeStatement($schema->toSql($this->connection->getDatabasePlatform())[0]);
            $this->logger->info("Table {$this->tableName} created successfully.");
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Error creating table {$this->tableName}: " . $e->getMessage());
            return false;
        }
    }

    public function down(): bool {
        try {
            $this->connection->executeStatement("DROP TABLE IF EXISTS {$this->tableName}");
            $this->logger->info("Table {$this->tableName} dropped successfully.");
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Error dropping table {$this->tableName}: " . $e->getMessage());
            return false;
        }
    }

    public function isApplied(): bool {
        $schemaManager = $this->connection->createSchemaManager();
        return $schemaManager->tablesExist([$this->tableName]);
    }

    public function getVersion(): string {
        return '1.0.0';
    }

    public function getDescription(): string {
        return 'Create condominiums table';
    }
}
