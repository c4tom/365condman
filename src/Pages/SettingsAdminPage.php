<?php
namespace CondMan\Pages;

use CondMan\Domain\Interfaces\AdminPageInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use CondMan\Domain\Services\ConfigurationService;

class SettingsAdminPage implements AdminPageInterface {
    private LoggerInterface $logger;
    private ConfigurationService $configService;

    public function __construct(
        LoggerInterface $logger,
        ConfigurationService $configService
    ) {
        $this->logger = $logger;
        $this->configService = $configService;
    }

    public function render(): void {
        try {
            $configurations = $this->configService->getAllConfigurations();
            ?>
            <div class="wrap">
                <h1>Configurações Avançadas</h1>
                <div class="settings-container">
                    <div class="settings-tabs">
                        <div class="nav-tab-wrapper">
                            <a href="#sistema" class="nav-tab nav-tab-active">Sistema</a>
                            <a href="#integracao" class="nav-tab">Integrações</a>
                            <a href="#seguranca" class="nav-tab">Segurança</a>
                            <a href="#notificacoes" class="nav-tab">Notificações</a>
                            <a href="#performance" class="nav-tab">Performance</a>
                        </div>
                    </div>
                    <div class="settings-content">
                        <form method="post" action="options.php">
                            <?php 
                            settings_fields('condman_settings_group');
                            do_settings_sections('condman-settings');
                            ?>
                            
                            <div id="sistema" class="tab-content active">
                                <h2>Configurações do Sistema</h2>
                                <table class="form-table">
                                    <tr>
                                        <th>Idioma Padrão</th>
                                        <td>
                                            <select name="condman_settings[default_language]">
                                                <option value="pt_BR">Português (Brasil)</option>
                                                <option value="en_US">English (US)</option>
                                                <option value="es_ES">Español</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Modo de Manutenção</th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="condman_settings[maintenance_mode]" value="1">
                                                Ativar modo de manutenção
                                            </label>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div id="integracao" class="tab-content">
                                <h2>Integrações Externas</h2>
                                <table class="form-table">
                                    <tr>
                                        <th>Integração Bancária</th>
                                        <td>
                                            <select name="condman_settings[bank_integration]">
                                                <option value="">Selecione um banco</option>
                                                <option value="itau">Itaú</option>
                                                <option value="bradesco">Bradesco</option>
                                                <option value="bb">Banco do Brasil</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Chave de API</th>
                                        <td>
                                            <input type="text" name="condman_settings[external_api_key]" 
                                                   class="regular-text" placeholder="Chave de integração">
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div id="seguranca" class="tab-content">
                                <h2>Configurações de Segurança</h2>
                                <table class="form-table">
                                    <tr>
                                        <th>Autenticação de Dois Fatores</th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="condman_settings[two_factor_auth]" value="1">
                                                Ativar autenticação de dois fatores
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Limite de Tentativas de Login</th>
                                        <td>
                                            <input type="number" name="condman_settings[login_attempts]" 
                                                   value="5" min="3" max="10">
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div id="notificacoes" class="tab-content">
                                <h2>Configurações de Notificação</h2>
                                <table class="form-table">
                                    <tr>
                                        <th>Canais de Notificação</th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="condman_settings[email_notifications]" value="1" checked>
                                                E-mail
                                            </label>
                                            <label>
                                                <input type="checkbox" name="condman_settings[sms_notifications]" value="1">
                                                SMS
                                            </label>
                                            <label>
                                                <input type="checkbox" name="condman_settings[whatsapp_notifications]" value="1">
                                                WhatsApp
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Frequência de Notificações</th>
                                        <td>
                                            <select name="condman_settings[notification_frequency]">
                                                <option value="immediate">Imediato</option>
                                                <option value="daily">Diário</option>
                                                <option value="weekly">Semanal</option>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div id="performance" class="tab-content">
                                <h2>Configurações de Performance</h2>
                                <table class="form-table">
                                    <tr>
                                        <th>Cache de Dados</th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="condman_settings[data_cache]" value="1" checked>
                                                Ativar cache de dados
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Tempo de Expiração do Cache</th>
                                        <td>
                                            <input type="number" name="condman_settings[cache_expiration]" 
                                                   value="24" min="1" max="168"> horas
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <?php submit_button('Salvar Configurações'); ?>
                        </form>
                    </div>
                </div>
            </div>
            <script>
            jQuery(document).ready(function($) {
                $('.nav-tab-wrapper .nav-tab').on('click', function(e) {
                    e.preventDefault();
                    $('.nav-tab').removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active');
                    
                    $('.tab-content').removeClass('active');
                    $($(this).attr('href')).addClass('active');
                });
            });
            </script>
            <?php
        } catch (\Exception $e) {
            $this->logger->error('Erro ao renderizar página de configurações', [
                'error' => $e->getMessage()
            ]);
            wp_die('Erro ao carregar página de configurações');
        }
    }

    public function registerHooks(): void {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function registerSettings(): void {
        register_setting(
            'condman_settings_group', 
            'condman_settings',
            [$this, 'sanitizeSettings']
        );

        add_settings_section(
            'condman_main_section', 
            'Configurações Principais', 
            [$this, 'mainSectionCallback'], 
            'condman-settings'
        );

        add_settings_field(
            'condman_license_key', 
            'Chave de Licença', 
            [$this, 'licenseKeyCallback'], 
            'condman-settings', 
            'condman_main_section'
        );
    }

    public function mainSectionCallback(): void {
        echo '<p>Configurações principais do plugin 365 Cond Man</p>';
    }

    public function licenseKeyCallback(): void {
        $settings = get_option('condman_settings');
        $licenseKey = $settings['license_key'] ?? '';
        echo "<input type='text' name='condman_settings[license_key]' value='" . 
             esc_attr($licenseKey) . "' class='regular-text'>";
    }

    public function sanitizeSettings($input): array {
        $output = [];
        $output['license_key'] = sanitize_text_field($input['license_key'] ?? '');
        return $output;
    }

    public function enqueueAssets(): void {
        wp_enqueue_style(
            'condman-settings-style', 
            CONDMAN_PLUGIN_URL . '/assets/css/admin-settings.css', 
            [], 
            CONDMAN_VERSION
        );
        wp_enqueue_script(
            'condman-settings-script', 
            CONDMAN_PLUGIN_URL . '/assets/js/admin-settings.js', 
            ['jquery'], 
            CONDMAN_VERSION, 
            true
        );
    }

    public function processFormActions(array $data): array {
        try {
            $result = $this->configService->updateConfigurations($data);
            
            $this->logger->info('Configurações atualizadas', [
                'updated_fields' => array_keys($data)
            ]);

            return [
                'success' => true,
                'message' => 'Configurações atualizadas com sucesso',
                'data' => $result
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erro ao processar configurações', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao atualizar configurações',
                'error' => $e->getMessage()
            ];
        }
    }

    public function getPageConfig(): array {
        return [
            'title' => 'Configurações 365 Cond Man',
            'menu_title' => 'Configurações',
            'slug' => 'condman-settings',
            'capability' => 'manage_options',
            'icon' => 'dashicons-admin-settings'
        ];
    }

    public function checkPermissions(?int $userId = null): bool {
        $userId = $userId ?? get_current_user_id();
        return current_user_can('manage_options', $userId);
    }
}
