<?php
/**
 * Classe principal do plugin 365 Cond Man
 *
 * @package CondMan\Core
 */

namespace CondMan\Core;

// Prevenir acesso direto ao arquivo
defined( 'ABSPATH' ) || exit;

/**
 * Classe principal do plugin
 */
class Plugin {
    /**
     * Instância singleton da classe
     *
     * @var Plugin
     */
    private static $instance = null;

    /**
     * Construtor privado para padrão singleton
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Inicializar hooks do plugin
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
    }

    /**
     * Registrar tipos de post personalizados
     */
    public function register_post_types() {
        // Exemplo: Registrar tipo de post para condomínios
        register_post_type(
            'condominium',
            array(
                'labels'             => array(
                    'name'               => __( 'Condomínios', '365condman' ),
                    'singular_name'      => __( 'Condomínio', '365condman' ),
                    'add_new'            => __( 'Adicionar Novo', '365condman' ),
                    'add_new_item'       => __( 'Adicionar Novo Condomínio', '365condman' ),
                    'edit_item'          => __( 'Editar Condomínio', '365condman' ),
                ),
                'public'             => true,
                'publicly_queryable' => true,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'query_var'          => true,
                'rewrite'            => array( 'slug' => 'condominium' ),
                'capability_type'    => 'post',
                'has_archive'        => true,
                'hierarchical'       => false,
                'menu_position'      => null,
                'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
            )
        );
    }

    /**
     * Registrar menu administrativo
     */
    public function register_admin_menu() {
        add_menu_page(
            __( '365 Cond Man', '365condman' ),
            __( '365 Cond Man', '365condman' ),
            'manage_options',
            '365condman',
            array( $this, 'render_admin_page' ),
            'dashicons-building',
            20
        );
    }

    /**
     * Renderizar página administrativa
     */
    public function render_admin_page() {
        // Renderizar template da página administrativa
        include_once CONDMAN_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Obter instância singleton
     *
     * @return Plugin
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
