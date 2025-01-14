<?php
namespace CondMan\Admin;

use CondMan\Domain\Interfaces\ConfigurationInterface;
use CondMan\Infrastructure\Logging\LoggingService;

class AdminManager {
    private $config;
    private $logger;
    private $menuSlug = '365condman';

    public function __construct(
        ConfigurationInterface $config,
        LoggingService $logger
    ) {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Inicializa hooks de administração
     */
    public function init(): void {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
    }

    /**
     * Adiciona menu de administração
     */
    public function addAdminMenu(): void {
        add_menu_page(
            '365 Cond Man', 
            '365 Cond Man', 
            'manage_options', 
            $this->menuSlug,
            [$this, 'renderMainPage'],
            'dashicons-building', 
            30
        );

        add_submenu_page(
            $this->menuSlug,
            'Configurações', 
            'Configurações', 
            'manage_options', 
            $this->menuSlug . '-settings',
            [$this, 'renderSettingsPage']
        );

        add_submenu_page(
            $this->menuSlug,
            'Logs', 
            'Logs', 
            'manage_options', 
            $this->menuSlug . '-logs',
            [$this, 'renderLogsPage']
        );
    }

    /**
     * Renderiza página principal
     */
    public function renderMainPage(): void {
        ?>
        <div class="wrap">
            <h1>365 Cond Man</h1>
            <div class="card">
                <h2>Bem-vindo ao Plugin de Gestão Condominial</h2>
                <p>Gerencie seu condomínio de forma simples e eficiente.</p>
                <div class="dashboard-widgets">
                    <div class="dashboard-widget">
                        <h3>Estatísticas Rápidas</h3>
                        <?php $this->renderQuickStats(); ?>
                    </div>
                    <div class="dashboard-widget">
                        <h3>Próximas Ações</h3>
                        <?php $this->renderNextActions(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza página de configurações
     */
    public function renderSettingsPage(): void {
        ?>
        <div class="wrap">
            <h1>Configurações - 365 Cond Man</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->menuSlug . '_settings');
                do_settings_sections($this->menuSlug . '_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Renderiza página de logs
     */
    public function renderLogsPage(): void {
        ?>
        <div class="wrap">
            <h1>Logs - 365 Cond Man</h1>
            <?php $this->renderRecentLogs(); ?>
        </div>
        <?php
    }

    /**
     * Registra configurações do plugin
     */
    public function registerSettings(): void {
        register_setting(
            $this->menuSlug . '_settings', 
            '365condman_smtp_host'
        );

        register_setting(
            $this->menuSlug . '_settings', 
            '365condman_smtp_port'
        );

        add_settings_section(
            $this->menuSlug . '_smtp_section', 
            'Configurações de SMTP', 
            [$this, 'renderSmtpSectionDescription'], 
            $this->menuSlug . '_settings'
        );

        add_settings_field(
            'smtp_host', 
            'Servidor SMTP', 
            [$this, 'renderSmtpHostField'], 
            $this->menuSlug . '_settings',
            $this->menuSlug . '_smtp_section'
        );

        add_settings_field(
            'smtp_port', 
            'Porta SMTP', 
            [$this, 'renderSmtpPortField'], 
            $this->menuSlug . '_settings',
            $this->menuSlug . '_smtp_section'
        );
    }

    /**
     * Renderiza descrição da seção SMTP
     */
    public function renderSmtpSectionDescription(): void {
        echo '<p>Configure as credenciais do servidor SMTP para envio de emails.</p>';
    }

    /**
     * Renderiza campo de host SMTP
     */
    public function renderSmtpHostField(): void {
        $value = get_option('365condman_smtp_host', 'localhost');
        echo "<input type='text' name='365condman_smtp_host' value='" . esc_attr($value) . "' />";
    }

    /**
     * Renderiza campo de porta SMTP
     */
    public function renderSmtpPortField(): void {
        $value = get_option('365condman_smtp_port', 587);
        echo "<input type='number' name='365condman_smtp_port' value='" . esc_attr($value) . "' />";
    }

    /**
     * Renderiza estatísticas rápidas
     */
    private function renderQuickStats(): void {
        // Implementação de estatísticas básicas
        echo '<ul>';
        echo '<li>Total de Condomínios: 0</li>';
        echo '<li>Total de Unidades: 0</li>';
        echo '<li>Faturas Pendentes: 0</li>';
        echo '</ul>';
    }

    /**
     * Renderiza próximas ações
     */
    private function renderNextActions(): void {
        echo '<ul>';
        echo '<li>Configurar Integração SMTP</li>';
        echo '<li>Revisar Configurações</li>';
        echo '<li>Verificar Logs</li>';
        echo '</ul>';
    }

    /**
     * Renderiza logs recentes
     */
    private function renderRecentLogs(): void {
        // Implementação simplificada de visualização de logs
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Data</th><th>Nível</th><th>Mensagem</th></tr></thead>';
        echo '<tbody>';
        echo '<tr><td colspan="3">Implementação de visualização de logs pendente</td></tr>';
        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Enfileira scripts e estilos de administração
     * 
     * @param string $hook Página atual
     */
    public function enqueueAdminScripts(string $hook): void {
        if (strpos($hook, $this->menuSlug) === false) {
            return;
        }

        wp_enqueue_style(
            '365condman-admin-style', 
            plugin_dir_url(__FILE__) . '../../assets/css/admin.css', 
            [], 
            '1.0.0'
        );

        wp_enqueue_script(
            '365condman-admin-script', 
            plugin_dir_url(__FILE__) . '../../assets/js/admin.js', 
            ['jquery'], 
            '1.0.0', 
            true
        );
    }
}
