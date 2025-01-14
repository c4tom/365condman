<?php
/**
 * Classe de ativação do plugin
 *
 * @package CondMan\Core
 */

namespace CondMan\Core;

// Prevenir acesso direto ao arquivo
defined( 'ABSPATH' ) || exit;

/**
 * Classe responsável por tarefas de ativação
 */
class Activator {
    /**
     * Método de ativação do plugin
     */
    public static function activate() {
        // Criar tabelas personalizadas, se necessário
        self::create_custom_tables();

        // Definir capabilities e roles
        self::set_capabilities();

        // Definir opções padrão
        self::set_default_options();
    }

    /**
     * Criar tabelas personalizadas no banco de dados
     */
    private static function create_custom_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $tables = array(
            "CREATE TABLE {$wpdb->prefix}condman_condominiums (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                address text NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset_collate;",

            "CREATE TABLE {$wpdb->prefix}condman_units (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                condominium_id mediumint(9) NOT NULL,
                unit_number varchar(50) NOT NULL,
                resident_name varchar(255),
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset_collate;"
        );

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ( $tables as $table ) {
            dbDelta( $table );
        }
    }

    /**
     * Definir capabilities e roles personalizadas
     */
    private static function set_capabilities() {
        $roles = array(
            'administrator' => array(
                '365condman_manage_condominiums',
                '365condman_manage_units',
                '365condman_manage_residents',
            ),
            'editor' => array(
                '365condman_view_condominiums',
                '365condman_view_units',
            ),
        );

        foreach ( $roles as $role_name => $capabilities ) {
            $role = get_role( $role_name );
            if ( $role ) {
                foreach ( $capabilities as $cap ) {
                    $role->add_cap( $cap );
                }
            }
        }
    }

    /**
     * Definir opções padrão do plugin
     */
    private static function set_default_options() {
        $default_options = array(
            '365condman_version'          => '1.0.0',
            '365condman_first_activation' => current_time( 'mysql' ),
            '365condman_enable_features'  => array(
                'condominiums' => true,
                'units'        => true,
                'residents'    => true,
            ),
        );

        foreach ( $default_options as $option => $value ) {
            add_option( $option, $value );
        }
    }
}
