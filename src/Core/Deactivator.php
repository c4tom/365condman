<?php
/**
 * Classe de desativação do plugin
 *
 * @package CondMan\Core
 */

namespace CondMan\Core;

// Prevenir acesso direto ao arquivo
defined( 'ABSPATH' ) || exit;

/**
 * Classe responsável por tarefas de desativação
 */
class Deactivator {
    /**
     * Método de desativação do plugin
     */
    public static function deactivate() {
        // Remover capabilities personalizadas
        self::remove_capabilities();

        // Limpar opções temporárias
        self::cleanup_options();
    }

    /**
     * Remover capabilities personalizadas
     */
    private static function remove_capabilities() {
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
                    $role->remove_cap( $cap );
                }
            }
        }
    }

    /**
     * Limpar opções do plugin
     */
    private static function cleanup_options() {
        $options_to_delete = array(
            '365condman_version',
            '365condman_first_activation',
            '365condman_enable_features',
        );

        foreach ( $options_to_delete as $option ) {
            delete_option( $option );
        }
    }
}
