<?php
namespace CondMan\Infrastructure\Migrations;

use CondMan\Domain\Interfaces\ConfigurationInterface;

class MigrationService {
    private $wpdb;
    private $config;
    private $migrationTable;
    private $migrations = [];

    public function __construct(
        \wpdb $wpdb, 
        ConfigurationInterface $config
    ) {
        $this->wpdb = $wpdb;
        $this->config = $config;
        $this->migrationTable = $this->wpdb->prefix . '365condman_migrations';
        
        // Registrar migrations
        $this->registerMigrations();
    }

    private function registerMigrations(): void {
        $this->migrations[] = new CreateUnitsTableMigration(
            $this->wpdb, 
            $this->config
        );
        // Adicionar futuras migrations aqui
    }

    /**
     * Executa todas as migrations pendentes
     * @return array Resultado das migrations
     */
    public function runMigrations(): array {
        $this->createMigrationTable();
        
        $results = [];
        foreach ($this->migrations as $migration) {
            if (!$migration->isApplied()) {
                $result = $migration->up();
                $this->logMigration(get_class($migration), $result);
                $results[get_class($migration)] = $result;
            }
        }
        
        return $results;
    }

    /**
     * Cria tabela de controle de migrations
     */
    private function createMigrationTable(): void {
        $charset = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationTable} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            migration VARCHAR(255) NOT NULL,
            applied_at DATETIME NOT NULL,
            status ENUM('success', 'failed') NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_migration (migration)
        ) {$charset};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Registra log de migration
     * @param string $migration Nome da migration
     * @param bool $status Status da migration
     */
    private function logMigration(string $migration, bool $status): void {
        $this->wpdb->insert(
            $this->migrationTable,
            [
                'migration' => $migration,
                'applied_at' => current_time('mysql'),
                'status' => $status ? 'success' : 'failed'
            ]
        );
    }

    /**
     * Reverte migrations
     * @return array Resultado das reversões
     */
    public function rollbackMigrations(): array {
        $results = [];
        foreach (array_reverse($this->migrations) as $migration) {
            if ($migration->isApplied()) {
                $result = $migration->down();
                $results[get_class($migration)] = $result;
            }
        }
        
        return $results;
    }
}
