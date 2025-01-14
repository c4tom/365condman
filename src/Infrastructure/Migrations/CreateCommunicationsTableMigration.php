<?php
namespace CondMan\Infrastructure\Migrations;

use CondMan\Domain\Interfaces\ConfigurationInterface;
use CondMan\Infrastructure\Logging\LoggingService;

class CreateCommunicationsTableMigration implements MigrationInterface {
    private $wpdb;
    private $config;
    private $logger;
    private $communicationsTableName;
    private $communicationTemplatesTableName;
    private $communicationLogsTableName;

    public function __construct(
        \wpdb $wpdb, 
        ConfigurationInterface $config,
        LoggingService $logger
    ) {
        $this->wpdb = $wpdb;
        $this->config = $config;
        $this->logger = $logger;
        $this->communicationsTableName = $this->wpdb->prefix . '365condman_communications';
        $this->communicationTemplatesTableName = $this->wpdb->prefix . '365condman_communication_templates';
        $this->communicationLogsTableName = $this->wpdb->prefix . '365condman_communication_logs';
    }

    public function up(): bool {
        $charset = $this->wpdb->get_charset_collate();

        $communicationTemplatesSql = "CREATE TABLE {$this->communicationTemplatesTableName} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            channel ENUM('email', 'sms', 'whatsapp', 'push_notification') NOT NULL,
            subject VARCHAR(255) NULL,
            content TEXT NOT NULL,
            type ENUM('invoice', 'reminder', 'warning', 'general', 'maintenance') NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_template (name, channel)
        ) {$charset};";

        $communicationsSql = "CREATE TABLE {$this->communicationsTableName} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            condominium_id BIGINT UNSIGNED NOT NULL,
            unit_id BIGINT UNSIGNED NULL,
            recipient_id BIGINT UNSIGNED NULL,
            template_id BIGINT UNSIGNED NOT NULL,
            channel ENUM('email', 'sms', 'whatsapp', 'push_notification') NOT NULL,
            recipient_email VARCHAR(100) NULL,
            recipient_phone VARCHAR(20) NULL,
            subject VARCHAR(255) NULL,
            content TEXT NOT NULL,
            status ENUM('pending', 'sent', 'failed', 'read') DEFAULT 'pending',
            scheduled_at DATETIME NULL,
            sent_at DATETIME NULL,
            retry_count TINYINT(2) DEFAULT 0,
            additional_data JSON NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_condominium (condominium_id),
            KEY idx_unit (unit_id),
            KEY idx_template (template_id),
            KEY idx_status (status),
            KEY idx_channel (channel),
            FOREIGN KEY (condominium_id) REFERENCES {$this->wpdb->prefix}365condman_condominiums(id) ON DELETE CASCADE,
            FOREIGN KEY (unit_id) REFERENCES {$this->wpdb->prefix}365condman_units(id) ON DELETE SET NULL,
            FOREIGN KEY (template_id) REFERENCES {$this->communicationTemplatesTableName}(id) ON DELETE RESTRICT
        ) {$charset};";

        $communicationLogsSql = "CREATE TABLE {$this->communicationLogsTableName} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            communication_id BIGINT UNSIGNED NOT NULL,
            event_type ENUM('created', 'sent', 'read', 'failed', 'retry') NOT NULL,
            details TEXT NULL,
            error_message TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_communication (communication_id),
            KEY idx_event_type (event_type),
            FOREIGN KEY (communication_id) REFERENCES {$this->communicationsTableName}(id) ON DELETE CASCADE
        ) {$charset};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($communicationTemplatesSql);
        dbDelta($communicationsSql);
        dbDelta($communicationLogsSql);

        $templatesTableExists = $this->wpdb->get_var(
            $this->wpdb->prepare(
                'SHOW TABLES LIKE %s', 
                $this->communicationTemplatesTableName
            )
        ) === $this->communicationTemplatesTableName;

        $communicationsTableExists = $this->wpdb->get_var(
            $this->wpdb->prepare(
                'SHOW TABLES LIKE %s', 
                $this->communicationsTableName
            )
        ) === $this->communicationsTableName;

        $communicationLogsTableExists = $this->wpdb->get_var(
            $this->wpdb->prepare(
                'SHOW TABLES LIKE %s', 
                $this->communicationLogsTableName
            )
        ) === $this->communicationLogsTableName;

        $success = $templatesTableExists && 
                   $communicationsTableExists && 
                   $communicationLogsTableExists;

        if ($success) {
            $this->logger->info('Tabelas de Comunicação criadas com sucesso', [
                'templates_table' => $this->communicationTemplatesTableName,
                'communications_table' => $this->communicationsTableName,
                'communication_logs_table' => $this->communicationLogsTableName,
                'method' => 'up'
            ]);
        } else {
            $this->logger->error('Falha ao criar tabelas de Comunicação', [
                'templates_table' => $this->communicationTemplatesTableName,
                'communications_table' => $this->communicationsTableName,
                'communication_logs_table' => $this->communicationLogsTableName,
                'method' => 'up'
            ]);
        }

        return $success;
    }

    public function down(): bool {
        $dropCommunicationLogsResult = $this->wpdb->query(
            "DROP TABLE IF EXISTS {$this->communicationLogsTableName}"
        );

        $dropCommunicationsResult = $this->wpdb->query(
            "DROP TABLE IF EXISTS {$this->communicationsTableName}"
        );

        $dropTemplatesResult = $this->wpdb->query(
            "DROP TABLE IF EXISTS {$this->communicationTemplatesTableName}"
        );

        $success = $dropCommunicationLogsResult !== false && 
                   $dropCommunicationsResult !== false && 
                   $dropTemplatesResult !== false;

        $this->logger->info('Tabelas de Comunicação removidas', [
            'templates_table' => $this->communicationTemplatesTableName,
            'communications_table' => $this->communicationsTableName,
            'communication_logs_table' => $this->communicationLogsTableName,
            'method' => 'down',
            'result' => $success
        ]);

        return $success;
    }

    public function getVersion(): string {
        return '1.0.0';
    }

    /**
     * Método para popular dados iniciais
     */
    public function seed(): void {
        // Criar templates de comunicação padrão
        $defaultTemplates = [
            [
                'name' => 'Invoice_Reminder',
                'channel' => 'email',
                'subject' => 'Lembrete de Fatura - {{condominium_name}}',
                'content' => 'Prezado(a) {{recipient_name}}, sua fatura de {{reference_month}}/{{reference_year}} está próxima do vencimento.',
                'type' => 'invoice',
                'is_active' => 1
            ],
            [
                'name' => 'Maintenance_Notification',
                'channel' => 'sms',
                'subject' => null,
                'content' => 'Aviso: Manutenção programada no condomínio {{condominium_name}} em {{maintenance_date}}.',
                'type' => 'maintenance',
                'is_active' => 1
            ]
        ];

        foreach ($defaultTemplates as $template) {
            $this->wpdb->insert(
                $this->communicationTemplatesTableName, 
                $template,
                ['%s', '%s', '%s', '%s', '%s', '%d']
            );
        }

        $this->logger->info('Templates de Comunicação iniciais criados', [
            'templates_table' => $this->communicationTemplatesTableName,
            'method' => 'seed',
            'templates_count' => count($defaultTemplates)
        ]);
    }
}
