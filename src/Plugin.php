<?php
namespace CondMan;

use CondMan\Infrastructure\Configuration\ConfigurationService;
use CondMan\Infrastructure\Logging\LoggingService;
use CondMan\Infrastructure\Migrations\MigrationService;
use CondMan\Admin\AdminManager;
use CondMan\Domain\Services\FinancialService;
use CondMan\Domain\Services\CommunicationService;

class Plugin {
    private const VERSION = '1.0.0';
    private const PLUGIN_SLUG = '365condman';

    private static $instance = null;
    private $config;
    private $logger;
    private $migrationService;
    private $adminManager;
    private $financialService;
    private $communicationService;

    /**
     * Singleton para garantir uma única instância
     * @return Plugin
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Construtor privado para singleton
     */
    private function __construct() {
        $this->initDependencies();
        $this->setupHooks();
    }

    /**
     * Inicializa dependências do plugin
     */
    private function initDependencies(): void {
        global $wpdb;

        // Inicializar serviços
        $this->config = new ConfigurationService($wpdb);
        $this->logger = new LoggingService($this->config);
        
        $this->migrationService = new MigrationService(
            $wpdb, 
            $this->config, 
            $this->logger
        );

        $this->adminManager = new AdminManager(
            $this->config, 
            $this->logger
        );

        $this->financialService = new FinancialService(
            $this->config, 
            $wpdb,
            new CommunicationService($this->config)
        );
    }

    /**
     * Configura hooks do WordPress
     */
    private function setupHooks(): void {
        register_activation_hook(
            plugin_basename(__FILE__), 
            [$this, 'activate']
        );

        register_deactivation_hook(
            plugin_basename(__FILE__), 
            [$this, 'deactivate']
        );

        add_action('plugins_loaded', [$this, 'loadTextDomain']);
        add_action('init', [$this, 'initPlugin']);
    }

    /**
     * Carrega domínio de tradução
     */
    public function loadTextDomain(): void {
        load_plugin_textdomain(
            self::PLUGIN_SLUG, 
            false, 
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    /**
     * Inicializa plugin
     */
    public function initPlugin(): void {
        // Inicializar componentes
        $this->adminManager->init();

        // Executar migrações
        try {
            $this->migrationService->runMigrations();
        } catch (\Exception $e) {
            $this->logger->logException($e);
        }
    }

    /**
     * Método de ativação do plugin
     */
    public function activate(): void {
        // Definir configurações padrão
        $this->config->restoreDefaults();

        // Executar migrações
        try {
            $this->migrationService->runMigrations();
        } catch (\Exception $e) {
            $this->logger->logException($e);
        }

        // Registrar capacidades para administradores
        $adminRole = get_role('administrator');
        $adminRole->add_cap('manage_365condman', true);

        // Adicionar primeira configuração de log
        $this->logger->info('Plugin 365 Cond Man ativado', [
            'version' => self::VERSION
        ]);
    }

    /**
     * Método de desativação do plugin
     */
    public function deactivate(): void {
        // Remover capacidades de administrador
        $adminRole = get_role('administrator');
        $adminRole->remove_cap('manage_365condman');

        // Log de desativação
        $this->logger->info('Plugin 365 Cond Man desativado');

        // Opcional: Limpar logs antigos
        $this->logger->cleanupLogs(7);
    }

    /**
     * Recupera versão do plugin
     * @return string
     */
    public function getVersion(): string {
        return self::VERSION;
    }

    /**
     * Recupera slug do plugin
     * @return string
     */
    public function getSlug(): string {
        return self::PLUGIN_SLUG;
    }

    /**
     * Recupera serviços
     * @return array
     */
    public function getServices(): array {
        return [
            'config' => $this->config,
            'logger' => $this->logger,
            'migrations' => $this->migrationService,
            'financial' => $this->financialService
        ];
    }
}
