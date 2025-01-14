<?php
namespace CondMan\Infrastructure\Migrations;

use CondMan\Domain\Interfaces\ConfigurationInterface;
use CondMan\Infrastructure\Logging\LoggingService;

class CreateUnitsTableMigration implements MigrationInterface {
    private $wpdb;
    private $config;
    private $logger;
    private $tableName;

    public function __construct(
        \wpdb $wpdb, 
        ConfigurationInterface $config,
        LoggingService $logger
    ) {
        $this->wpdb = $wpdb;
        $this->config = $config;
        $this->logger = $logger;
        $this->tableName = $this->wpdb->prefix . '365condman_units';
    }

    public function up(): bool {
        $charset = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->tableName} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            condominium_id BIGINT UNSIGNED NOT NULL,
            owner_id BIGINT UNSIGNED NULL,
            tenant_id BIGINT UNSIGNED NULL,
            block VARCHAR(50) NULL,
            number VARCHAR(20) NOT NULL,
            type ENUM('residential', 'commercial', 'parking', 'storage', 'common_area') DEFAULT 'residential',
            area DECIMAL(10,2) NULL,
            fraction DECIMAL(5,4) NULL COMMENT 'Fração ideal do condomínio',
            status ENUM('occupied', 'vacant', 'maintenance', 'under_renovation') DEFAULT 'vacant',
            additional_info TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_unit (condominium_id, block, number),
            KEY idx_condominium (condominium_id),
            KEY idx_block (block),
            KEY idx_type (type),
            KEY idx_status (status),
            FOREIGN KEY (condominium_id) REFERENCES {$this->wpdb->prefix}365condman_condominiums(id) ON DELETE CASCADE
        ) {$charset};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql);

        $tableExists = $this->wpdb->get_var(
            $this->wpdb->prepare(
                'SHOW TABLES LIKE %s', 
                $this->tableName
            )
        ) === $this->tableName;

        if ($tableExists) {
            $this->logger->info('Tabela de Unidades criada com sucesso', [
                'table' => $this->tableName,
                'method' => 'up'
            ]);
        } else {
            $this->logger->error('Falha ao criar tabela de Unidades', [
                'table' => $this->tableName,
                'method' => 'up'
            ]);
        }

        return $tableExists;
    }

    public function down(): bool {
        $result = $this->wpdb->query("DROP TABLE IF EXISTS {$this->tableName}");

        $this->logger->info('Tabela de Unidades removida', [
            'table' => $this->tableName,
            'method' => 'down',
            'result' => $result
        ]);

        return $result !== false;
    }

    public function getVersion(): string {
        return '1.0.1';
    }

    /**
     * Método para popular dados iniciais
     */
    public function seed(): void {
        // Exemplo de dados iniciais para unidades
        $sampleUnits = [
            [
                'condominium_id' => 1,
                'block' => 'A',
                'number' => '101',
                'type' => 'residential',
                'area' => 75.50,
                'fraction' => 0.0075,
                'status' => 'vacant'
            ],
            [
                'condominium_id' => 1,
                'block' => 'A',
                'number' => '102',
                'type' => 'residential',
                'area' => 80.25,
                'fraction' => 0.0080,
                'status' => 'occupied'
            ]
        ];

        foreach ($sampleUnits as $unit) {
            $this->wpdb->insert(
                $this->tableName, 
                $unit,
                ['%d', '%s', '%s', '%s', '%f', '%f', '%s']
            );
        }

        $this->logger->info('Dados iniciais de Unidades populados', [
            'table' => $this->tableName,
            'method' => 'seed',
            'units_count' => count($sampleUnits)
        ]);
    }
}
