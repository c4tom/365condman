<?php
namespace CondMan\Infrastructure\Migrations;

use CondMan\Domain\Interfaces\MigrationInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;

class CreateUnitTableMigration implements MigrationInterface {
    private Connection $connection;
    private LoggerInterface $logger;
    private string $tableName;
    private string $condominiumTableName;

    public function __construct(Connection $connection, LoggerInterface $logger) {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->tableName = 'wp_condman_units';
        $this->condominiumTableName = 'wp_condman_condominiums';
    }

    public function up(): bool {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist([$this->tableName])) {
            $this->logger->info("Table {$this->tableName} already exists.");
            return true;
        }

        $schema = new Schema();
        $unitTable = $schema->createTable($this->tableName);

        $unitTable->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $unitTable->addColumn('condominium_id', Types::INTEGER);
        $unitTable->addColumn('block', Types::STRING, ['length' => 50, 'notnull' => false]);
        $unitTable->addColumn('number', Types::STRING, ['length' => 50]);
        $unitTable->addColumn('type', Types::STRING, ['length' => 50]);
        $unitTable->addColumn('area', Types::DECIMAL, ['precision' => 10, 'scale' => 2]);
        $unitTable->addColumn('fraction', Types::DECIMAL, ['precision' => 5, 'scale' => 4]);
        $unitTable->addColumn('status', Types::STRING, ['length' => 50]);
        $unitTable->addColumn('created_at', Types::DATETIME_MUTABLE);
        $unitTable->addColumn('updated_at', Types::DATETIME_MUTABLE);

        $unitTable->setPrimaryKey(['id']);
        $unitTable->addForeignKey(
            ['condominium_id'], 
            $this->condominiumTableName, 
            ['id'], 
            ['onDelete' => 'CASCADE']
        );

        $unitTable->addUniqueIndex(['condominium_id', 'block', 'number']);

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
        return 'Create units table';
    }
}
