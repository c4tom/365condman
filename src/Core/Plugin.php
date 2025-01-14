<?php
namespace CondMan\Core;

defined('ABSPATH') or die('Acesso direto não permitido.');

class Plugin {
    private $version = '1.0.0';

    public function __construct() {
        // Inicialização do plugin
    }

    public function init() {
        // Carregar textdomain para internacionalização
        load_plugin_textdomain('365condman', false, dirname(plugin_basename(CONDMAN_PLUGIN_FILE)) . '/languages/');

        // Registrar hooks e ações iniciais
        $this->registerHooks();
    }

    private function registerHooks() {
        // Adicionar hooks administrativos
        add_action('admin_menu', [$this, 'addAdminMenu']);
    }

    public function addAdminMenu() {
        // Adicionar menu administrativo inicial
        add_menu_page(
            '365 Cond Man', 
            '365 Cond Man', 
            'manage_options', 
            '365condman', 
            [$this, 'renderAdminPage'],
            'dashicons-building',
            20
        );
    }

    public function renderAdminPage() {
        // Renderizar página administrativa inicial
        echo '<div class="wrap">';
        echo '<h1>365 Cond Man</h1>';
        echo '<p>Sistema de Gestão Condominial</p>';
        echo '</div>';
    }

    public function getVersion() {
        return $this->version;
    }

    public function activate() {
        // Verificar requisitos mínimos
        $this->checkRequirements();

        // Tarefas de ativação
        $this->createInitialDatabaseTables();
    }

    private function checkRequirements() {
        $php_version = '8.0';
        $wp_version  = '6.4';

        if (version_compare(PHP_VERSION, $php_version, '<')) {
            deactivate_plugins(plugin_basename(CONDMAN_PLUGIN_FILE));
            wp_die(
                sprintf(
                    __('Este plugin requer PHP versão %1$s ou superior. Sua versão atual é %2$s.', '365condman'),
                    $php_version,
                    PHP_VERSION
                )
            );
        }

        if (version_compare($GLOBALS['wp_version'], $wp_version, '<')) {
            deactivate_plugins(plugin_basename(CONDMAN_PLUGIN_FILE));
            wp_die(
                sprintf(
                    __('Este plugin requer WordPress versão %1$s ou superior. Sua versão atual é %2$s.', '365condman'),
                    $wp_version,
                    $GLOBALS['wp_version']
                )
            );
        }
    }

    private function createInitialDatabaseTables() {
        global $wpdb;

        // Exemplo de criação de tabela inicial
        $table_name = $wpdb->prefix . 'condman_initial';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function deactivate() {
        // Tarefas de desativação
        // Por exemplo, limpar opções, remover tabelas temporárias
    }
}
