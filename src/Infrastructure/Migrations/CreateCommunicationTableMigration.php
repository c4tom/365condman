<?php
namespace CondMan\Infrastructure\Migrations;

use CondMan\Domain\Interfaces\MigrationInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;

class CreateCommunicationTableMigration implements MigrationInterface {
    private Connection $connection;
    private LoggerInterface $logger;
    private string $tableName;
    private string $templateTableName;
    private string $logTableName;
    private string $condominiumTableName;
    private string $unitTableName;

    public function __construct(Connection $connection, LoggerInterface $logger) {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->tableName = 'wp_condman_communications';
        $this->templateTableName = 'wp_condman_communication_templates';
        $this->logTableName = 'wp_condman_communication_logs';
        $this->condominiumTableName = 'wp_condman_condominiums';
        $this->unitTableName = 'wp_condman_units';
    }

    public function up(): bool {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist([
            $this->tableName, 
            $this->templateTableName, 
            $this->logTableName
        ])) {
            $this->logger->info("Communication related tables already exist.");
            return true;
        }

        $schema = new Schema();
        
        // Tabela de Comunicações
        $communicationTable = $schema->createTable($this->tableName);
        $communicationTable->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $communicationTable->addColumn('condominium_id', Types::INTEGER);
        $communicationTable->addColumn('unit_id', Types::INTEGER, ['notnull' => false]);
        $communicationTable->addColumn('channel', Types::STRING, ['length' => 50]);
        $communicationTable->addColumn('recipient', Types::STRING, ['length' => 255]);
        $communicationTable->addColumn('subject', Types::STRING, ['length' => 255]);
        $communicationTable->addColumn('content', Types::TEXT);
        $communicationTable->addColumn('status', Types::STRING, ['length' => 50]);
        $communicationTable->addColumn('additional_data', Types::JSON, ['notnull' => false]);
        $communicationTable->addColumn('sent_at', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $communicationTable->addColumn('read_at', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $communicationTable->addColumn('created_at', Types::DATETIME_MUTABLE);
        $communicationTable->addColumn('updated_at', Types::DATETIME_MUTABLE);

        $communicationTable->setPrimaryKey(['id']);
        $communicationTable->addForeignKey(
            ['condominium_id'], 
            $this->condominiumTableName, 
            ['id'], 
            ['onDelete' => 'CASCADE']
        );
        $communicationTable->addForeignKey(
            ['unit_id'], 
            $this->unitTableName, 
            ['id'], 
            ['onDelete' => 'SET NULL']
        );

        // Tabela de Templates de Comunicação
        $templateTable = $schema->createTable($this->templateTableName);
        $templateTable->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $templateTable->addColumn('name', Types::STRING, ['length' => 255]);
        $templateTable->addColumn('channel', Types::STRING, ['length' => 50]);
        $templateTable->addColumn('subject', Types::STRING, ['length' => 255]);
        $templateTable->addColumn('content', Types::TEXT);
        $templateTable->addColumn('is_active', Types::BOOLEAN, ['default' => true]);
        $templateTable->addColumn('created_at', Types::DATETIME_MUTABLE);
        $templateTable->addColumn('updated_at', Types::DATETIME_MUTABLE);

        $templateTable->setPrimaryKey(['id']);
        $templateTable->addUniqueIndex(['name', 'channel']);

        // Tabela de Logs de Comunicação
        $logTable = $schema->createTable($this->logTableName);
        $logTable->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $logTable->addColumn('communication_id', Types::INTEGER);
        $logTable->addColumn('event_type', Types::STRING, ['length' => 50]);
        $logTable->addColumn('description', Types::TEXT);
        $logTable->addColumn('additional_data', Types::JSON, ['notnull' => false]);
        $logTable->addColumn('created_at', Types::DATETIME_MUTABLE);

        $logTable->setPrimaryKey(['id']);
        $logTable->addForeignKey(
            ['communication_id'], 
            $this->tableName, 
            ['id'], 
            ['onDelete' => 'CASCADE']
        );

        try {
            $platform = $this->connection->getDatabasePlatform();
            $sqlStatements = $schema->toSql($platform);
            
            foreach ($sqlStatements as $statement) {
                $this->connection->executeStatement($statement);
            }
            
            $this->logger->info("Communication related tables created successfully.");
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Error creating communication tables: " . $e->getMessage());
            return false;
        }
    }

    public function down(): bool {
        try {
            $this->connection->executeStatement("DROP TABLE IF EXISTS {$this->logTableName}");
            $this->connection->executeStatement("DROP TABLE IF EXISTS {$this->templateTableName}");
            $this->connection->executeStatement("DROP TABLE IF EXISTS {$this->tableName}");
            
            $this->logger->info("Communication related tables dropped successfully.");
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Error dropping communication tables: " . $e->getMessage());
            return false;
        }
    }

    public function isApplied(): bool {
        $schemaManager = $this->connection->createSchemaManager();
        return $schemaManager->tablesExist([
            $this->tableName, 
            $this->templateTableName, 
            $this->logTableName
        ]);
    }

    public function getVersion(): string {
        return '1.0.0';
    }

    public function getDescription(): string {
        return 'Create communications, communication templates, and communication logs tables';
    }
}
