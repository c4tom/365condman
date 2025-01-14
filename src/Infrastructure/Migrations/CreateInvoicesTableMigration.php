<?php
namespace CondMan\Infrastructure\Migrations;

use CondMan\Domain\Interfaces\ConfigurationInterface;
use CondMan\Infrastructure\Logging\LoggingService;

class CreateInvoicesTableMigration implements MigrationInterface {
    private $wpdb;
    private $config;
    private $logger;
    private $invoicesTableName;
    private $invoiceItemsTableName;
    private $invoicePaymentsTableName;

    public function __construct(
        \wpdb $wpdb, 
        ConfigurationInterface $config,
        LoggingService $logger
    ) {
        $this->wpdb = $wpdb;
        $this->config = $config;
        $this->logger = $logger;
        $this->invoicesTableName = $this->wpdb->prefix . '365condman_invoices';
        $this->invoiceItemsTableName = $this->wpdb->prefix . '365condman_invoice_items';
        $this->invoicePaymentsTableName = $this->wpdb->prefix . '365condman_invoice_payments';
    }

    public function up(): bool {
        $charset = $this->wpdb->get_charset_collate();

        $invoicesSql = "CREATE TABLE {$this->invoicesTableName} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            condominium_id BIGINT UNSIGNED NOT NULL,
            unit_id BIGINT UNSIGNED NOT NULL,
            reference_month VARCHAR(2) NOT NULL,
            reference_year VARCHAR(4) NOT NULL,
            due_date DATE NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status ENUM('pending', 'partial', 'paid', 'overdue', 'canceled') DEFAULT 'pending',
            payment_method ENUM('bank_slip', 'pix', 'credit_card', 'bank_transfer', 'cash') NULL,
            additional_info TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_invoice (condominium_id, unit_id, reference_month, reference_year),
            KEY idx_condominium (condominium_id),
            KEY idx_unit (unit_id),
            KEY idx_reference (reference_month, reference_year),
            KEY idx_status (status),
            FOREIGN KEY (condominium_id) REFERENCES {$this->wpdb->prefix}365condman_condominiums(id) ON DELETE CASCADE,
            FOREIGN KEY (unit_id) REFERENCES {$this->wpdb->prefix}365condman_units(id) ON DELETE CASCADE
        ) {$charset};";

        $invoiceItemsSql = "CREATE TABLE {$this->invoiceItemsTableName} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            invoice_id BIGINT UNSIGNED NOT NULL,
            description VARCHAR(255) NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            type ENUM('monthly', 'extra', 'reserve', 'maintenance', 'utility', 'fine', 'other') DEFAULT 'monthly',
            tax_rate DECIMAL(5,2) NULL COMMENT 'Taxa adicional em porcentagem',
            is_mandatory TINYINT(1) DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_invoice (invoice_id),
            KEY idx_type (type),
            FOREIGN KEY (invoice_id) REFERENCES {$this->invoicesTableName}(id) ON DELETE CASCADE
        ) {$charset};";

        $invoicePaymentsSql = "CREATE TABLE {$this->invoicePaymentsTableName} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            invoice_id BIGINT UNSIGNED NOT NULL,
            payment_date DATE NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method ENUM('bank_slip', 'pix', 'credit_card', 'bank_transfer', 'cash') NOT NULL,
            transaction_id VARCHAR(100) NULL,
            receipt_url VARCHAR(255) NULL,
            additional_info TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_invoice (invoice_id),
            KEY idx_payment_method (payment_method),
            FOREIGN KEY (invoice_id) REFERENCES {$this->invoicesTableName}(id) ON DELETE CASCADE
        ) {$charset};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($invoicesSql);
        dbDelta($invoiceItemsSql);
        dbDelta($invoicePaymentsSql);

        $invoicesTableExists = $this->wpdb->get_var(
            $this->wpdb->prepare(
                'SHOW TABLES LIKE %s', 
                $this->invoicesTableName
            )
        ) === $this->invoicesTableName;

        $invoiceItemsTableExists = $this->wpdb->get_var(
            $this->wpdb->prepare(
                'SHOW TABLES LIKE %s', 
                $this->invoiceItemsTableName
            )
        ) === $this->invoiceItemsTableName;

        $invoicePaymentsTableExists = $this->wpdb->get_var(
            $this->wpdb->prepare(
                'SHOW TABLES LIKE %s', 
                $this->invoicePaymentsTableName
            )
        ) === $this->invoicePaymentsTableName;

        $success = $invoicesTableExists && $invoiceItemsTableExists && $invoicePaymentsTableExists;

        if ($success) {
            $this->logger->info('Tabelas de Faturas criadas com sucesso', [
                'invoices_table' => $this->invoicesTableName,
                'invoice_items_table' => $this->invoiceItemsTableName,
                'invoice_payments_table' => $this->invoicePaymentsTableName,
                'method' => 'up'
            ]);
        } else {
            $this->logger->error('Falha ao criar tabelas de Faturas', [
                'invoices_table' => $this->invoicesTableName,
                'invoice_items_table' => $this->invoiceItemsTableName,
                'invoice_payments_table' => $this->invoicePaymentsTableName,
                'method' => 'up'
            ]);
        }

        return $success;
    }

    public function down(): bool {
        $dropPaymentsResult = $this->wpdb->query("DROP TABLE IF EXISTS {$this->invoicePaymentsTableName}");
        $dropItemsResult = $this->wpdb->query("DROP TABLE IF EXISTS {$this->invoiceItemsTableName}");
        $dropInvoicesResult = $this->wpdb->query("DROP TABLE IF EXISTS {$this->invoicesTableName}");

        $success = $dropPaymentsResult !== false && 
                   $dropItemsResult !== false && 
                   $dropInvoicesResult !== false;

        $this->logger->info('Tabelas de Faturas removidas', [
            'invoices_table' => $this->invoicesTableName,
            'invoice_items_table' => $this->invoiceItemsTableName,
            'invoice_payments_table' => $this->invoicePaymentsTableName,
            'method' => 'down',
            'result' => $success
        ]);

        return $success;
    }

    public function getVersion(): string {
        return '1.0.1';
    }

    /**
     * Método para popular dados iniciais
     */
    public function seed(): void {
        // Exemplo de dados iniciais para faturas
        $sampleInvoice = [
            'condominium_id' => 1,
            'unit_id' => 1,
            'reference_month' => '01',
            'reference_year' => '2024',
            'due_date' => '2024-01-30',
            'total_amount' => 500.00,
            'status' => 'pending'
        ];

        $invoiceId = $this->wpdb->insert(
            $this->invoicesTableName, 
            $sampleInvoice,
            ['%d', '%d', '%s', '%s', '%s', '%f', '%s']
        );

        $sampleInvoiceItems = [
            [
                'invoice_id' => $invoiceId,
                'description' => 'Taxa de Condomínio',
                'amount' => 350.00,
                'type' => 'monthly'
            ],
            [
                'invoice_id' => $invoiceId,
                'description' => 'Fundo de Reserva',
                'amount' => 150.00,
                'type' => 'reserve'
            ]
        ];

        foreach ($sampleInvoiceItems as $item) {
            $this->wpdb->insert(
                $this->invoiceItemsTableName, 
                $item,
                ['%d', '%s', '%f', '%s']
            );
        }

        $this->logger->info('Dados iniciais de Faturas populados', [
            'invoices_table' => $this->invoicesTableName,
            'invoice_items_table' => $this->invoiceItemsTableName,
            'method' => 'seed',
            'invoice_id' => $invoiceId
        ]);
    }
}
