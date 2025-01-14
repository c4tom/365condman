<?php
namespace CondMan\Infrastructure\Migrations;

use CondMan\Domain\Interfaces\MigrationInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;

class CreateInvoiceTableMigration implements MigrationInterface {
    private Connection $connection;
    private LoggerInterface $logger;
    private string $tableName;
    private string $condominiumTableName;
    private string $unitTableName;
    private string $invoiceItemTableName;
    private string $paymentTableName;

    public function __construct(Connection $connection, LoggerInterface $logger) {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->tableName = 'wp_condman_invoices';
        $this->condominiumTableName = 'wp_condman_condominiums';
        $this->unitTableName = 'wp_condman_units';
        $this->invoiceItemTableName = 'wp_condman_invoice_items';
        $this->paymentTableName = 'wp_condman_payments';
    }

    public function up(): bool {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist([$this->tableName, $this->invoiceItemTableName, $this->paymentTableName])) {
            $this->logger->info("Invoice related tables already exist.");
            return true;
        }

        $schema = new Schema();
        
        // Tabela de Faturas
        $invoiceTable = $schema->createTable($this->tableName);
        $invoiceTable->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $invoiceTable->addColumn('condominium_id', Types::INTEGER);
        $invoiceTable->addColumn('unit_id', Types::INTEGER);
        $invoiceTable->addColumn('reference_month', Types::INTEGER);
        $invoiceTable->addColumn('reference_year', Types::INTEGER);
        $invoiceTable->addColumn('total_amount', Types::DECIMAL, ['precision' => 10, 'scale' => 2]);
        $invoiceTable->addColumn('total_paid', Types::DECIMAL, ['precision' => 10, 'scale' => 2, 'default' => 0]);
        $invoiceTable->addColumn('status', Types::STRING, ['length' => 50]);
        $invoiceTable->addColumn('due_date', Types::DATE_MUTABLE);
        $invoiceTable->addColumn('payment_date', Types::DATE_MUTABLE, ['notnull' => false]);
        $invoiceTable->addColumn('created_at', Types::DATETIME_MUTABLE);
        $invoiceTable->addColumn('updated_at', Types::DATETIME_MUTABLE);

        $invoiceTable->setPrimaryKey(['id']);
        $invoiceTable->addForeignKey(
            ['condominium_id'], 
            $this->condominiumTableName, 
            ['id'], 
            ['onDelete' => 'CASCADE']
        );
        $invoiceTable->addForeignKey(
            ['unit_id'], 
            $this->unitTableName, 
            ['id'], 
            ['onDelete' => 'CASCADE']
        );

        // Tabela de Itens de Fatura
        $invoiceItemTable = $schema->createTable($this->invoiceItemTableName);
        $invoiceItemTable->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $invoiceItemTable->addColumn('invoice_id', Types::INTEGER);
        $invoiceItemTable->addColumn('description', Types::STRING, ['length' => 255]);
        $invoiceItemTable->addColumn('amount', Types::DECIMAL, ['precision' => 10, 'scale' => 2]);
        $invoiceItemTable->addColumn('quantity', Types::DECIMAL, ['precision' => 10, 'scale' => 2, 'default' => 1]);

        $invoiceItemTable->setPrimaryKey(['id']);
        $invoiceItemTable->addForeignKey(
            ['invoice_id'], 
            $this->tableName, 
            ['id'], 
            ['onDelete' => 'CASCADE']
        );

        // Tabela de Pagamentos
        $paymentTable = $schema->createTable($this->paymentTableName);
        $paymentTable->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $paymentTable->addColumn('invoice_id', Types::INTEGER);
        $paymentTable->addColumn('amount', Types::DECIMAL, ['precision' => 10, 'scale' => 2]);
        $paymentTable->addColumn('payment_method', Types::STRING, ['length' => 50]);
        $paymentTable->addColumn('payment_date', Types::DATETIME_MUTABLE);

        $paymentTable->setPrimaryKey(['id']);
        $paymentTable->addForeignKey(
            ['invoice_id'], 
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
            
            $this->logger->info("Invoice related tables created successfully.");
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Error creating invoice tables: " . $e->getMessage());
            return false;
        }
    }

    public function down(): bool {
        try {
            $this->connection->executeStatement("DROP TABLE IF EXISTS {$this->paymentTableName}");
            $this->connection->executeStatement("DROP TABLE IF EXISTS {$this->invoiceItemTableName}");
            $this->connection->executeStatement("DROP TABLE IF EXISTS {$this->tableName}");
            
            $this->logger->info("Invoice related tables dropped successfully.");
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Error dropping invoice tables: " . $e->getMessage());
            return false;
        }
    }

    public function isApplied(): bool {
        $schemaManager = $this->connection->createSchemaManager();
        return $schemaManager->tablesExist([
            $this->tableName, 
            $this->invoiceItemTableName, 
            $this->paymentTableName
        ]);
    }

    public function getVersion(): string {
        return '1.0.0';
    }

    public function getDescription(): string {
        return 'Create invoices, invoice items, and payments tables';
    }
}
