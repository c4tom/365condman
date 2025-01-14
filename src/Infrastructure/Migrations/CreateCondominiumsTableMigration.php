<?php
namespace CondMan\Infrastructure\Migrations;

use CondMan\Infrastructure\Migrations\MigrationInterface;
use wpdb;

class CreateCondominiumsTableMigration implements MigrationInterface {
    private $wpdb;

    public function __construct(wpdb $wpdb) {
        $this->wpdb = $wpdb;
    }

    public function up(): bool {
        $tableName = $this->wpdb->prefix . '365condman_condominiums';
        
        $charset = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE $tableName (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            cnpj VARCHAR(18) NOT NULL,
            address TEXT NOT NULL,
            city VARCHAR(100) NOT NULL,
            state VARCHAR(2) NOT NULL,
            postal_code VARCHAR(10) NOT NULL,
            contact_email VARCHAR(100) NULL,
            contact_phone VARCHAR(20) NULL,
            total_units INT UNSIGNED DEFAULT 0,
            active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY cnpj (cnpj)
        ) $charset;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql);

        return true;
    }

    public function down(): bool {
        $tableName = $this->wpdb->prefix . '365condman_condominiums';
        
        $this->wpdb->query("DROP TABLE IF EXISTS $tableName");

        return true;
    }

    public function getVersion(): string {
        return '1.0.0';
    }
}
