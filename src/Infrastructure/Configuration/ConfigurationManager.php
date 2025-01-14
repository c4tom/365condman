<?php
/**
 * Gerenciador de Configurações do Plugin
 *
 * @package CondMan\Infrastructure\Configuration
 */

namespace CondMan\Infrastructure\Configuration;

use CondMan\Domain\Interfaces\ConfigurationManagerInterface;

/**
 * Implementação do gerenciamento de configurações
 */
class ConfigurationManager implements ConfigurationManagerInterface {
    /**
     * Armazena as configurações
     *
     * @var array
     */
    private $settings = [];

    /**
     * Obtém um valor de configuração
     *
     * @param string $key Chave da configuração
     * @param mixed $default Valor padrão
     * @return mixed
     */
    public function get(string $key, $default = null) {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Define um valor de configuração
     *
     * @param string $key Chave da configuração
     * @param mixed $value Valor da configuração
     * @return void
     */
    public function set(string $key, $value): void {
        $this->settings[$key] = $value;
    }

    /**
     * Verifica se uma configuração existe
     *
     * @param string $key Chave da configuração
     * @return bool
     */
    public function has(string $key): bool {
        return isset($this->settings[$key]);
    }

    /**
     * Carrega configurações de uma fonte específica
     *
     * @param string $source Fonte das configurações
     * @return void
     */
    public function load(string $source): void {
        switch ($source) {
            case 'env':
                $this->loadEnvironmentConfig();
                break;
            case 'wordpress':
                $this->loadWordPressConfig();
                break;
            case 'database':
                $this->loadDatabaseConfig();
                break;
        }
    }

    /**
     * Carrega configurações de ambiente
     *
     * @return void
     */
    private function loadEnvironmentConfig(): void {
        // Implementação futura de carregamento de .env
        $this->settings['debug_mode'] = defined('WP_DEBUG') ? WP_DEBUG : false;
    }

    /**
     * Carrega configurações do WordPress
     *
     * @return void
     */
    private function loadWordPressConfig(): void {
        $this->settings['max_condominiums'] = get_option('365condman_max_condominiums', 10);
        $this->settings['plugin_version'] = defined('CONDMAN_PLUGIN_VERSION') ? CONDMAN_PLUGIN_VERSION : '0.1.0';
    }

    /**
     * Carrega configurações do banco de dados
     *
     * @return void
     */
    private function loadDatabaseConfig(): void {
        // Implementação futura de carregamento de configurações do banco
    }
}
