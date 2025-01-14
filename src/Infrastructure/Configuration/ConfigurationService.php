<?php
namespace CondMan\Infrastructure\Configuration;

use CondMan\Domain\Interfaces\ConfigurationInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ConfigurationService implements ConfigurationInterface {
    private const OPTION_PREFIX = '365condman_';
    private $wpdb;
    private $logger;

    public function __construct(\wpdb $wpdb, Logger $logger = null) {
        $this->wpdb = $wpdb;
        $this->logger = $logger ?? $this->createLogger();
    }

    /**
     * Cria logger personalizado
     * @return Logger
     */
    private function createLogger(): Logger {
        $logger = new Logger('configuration');
        $logger->pushHandler(
            new StreamHandler(
                WP_CONTENT_DIR . '/logs/365condman-configuration.log', 
                Logger::INFO
            )
        );
        return $logger;
    }

    /**
     * Obtém valor de configuração
     * 
     * @param string $key Chave de configuração
     * @param mixed $default Valor padrão
     * @return mixed Valor da configuração
     */
    public function get(string $key, $default = null) {
        $optionKey = self::OPTION_PREFIX . $key;
        $value = get_option($optionKey, $default);

        $this->logger->info('Configuração obtida', [
            'key' => $key,
            'value' => is_scalar($value) ? $value : gettype($value)
        ]);

        return $value;
    }

    /**
     * Define valor de configuração
     * 
     * @param string $key Chave de configuração
     * @param mixed $value Valor a ser definido
     * @return bool Sucesso na definição
     */
    public function set(string $key, $value): bool {
        $optionKey = self::OPTION_PREFIX . $key;
        $result = update_option($optionKey, $value);

        $this->logger->info('Configuração definida', [
            'key' => $key,
            'value' => is_scalar($value) ? $value : gettype($value),
            'result' => $result
        ]);

        return $result;
    }

    /**
     * Remove configuração
     * 
     * @param string $key Chave de configuração
     * @return bool Sucesso na remoção
     */
    public function delete(string $key): bool {
        $optionKey = self::OPTION_PREFIX . $key;
        $result = delete_option($optionKey);

        $this->logger->info('Configuração removida', [
            'key' => $key,
            'result' => $result
        ]);

        return $result;
    }

    /**
     * Verifica se configuração existe
     * 
     * @param string $key Chave de configuração
     * @return bool Existência da configuração
     */
    public function has(string $key): bool {
        $optionKey = self::OPTION_PREFIX . $key;
        $exists = false !== get_option($optionKey);

        $this->logger->info('Verificação de existência de configuração', [
            'key' => $key,
            'exists' => $exists
        ]);

        return $exists;
    }

    /**
     * Lista todas as configurações do plugin
     * 
     * @return array Configurações do plugin
     */
    public function listAll(): array {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT option_name, option_value 
             FROM {$wpdb->options} 
             WHERE option_name LIKE %s",
            self::OPTION_PREFIX . '%'
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        $configurations = [];
        foreach ($results as $result) {
            $key = str_replace(self::OPTION_PREFIX, '', $result['option_name']);
            $configurations[$key] = maybe_unserialize($result['option_value']);
        }

        $this->logger->info('Listagem de todas as configurações', [
            'total_configs' => count($configurations)
        ]);

        return $configurations;
    }

    /**
     * Restaura configurações padrão
     * 
     * @return bool Sucesso na restauração
     */
    public function restoreDefaults(): bool {
        $defaults = [
            'smtp_host' => 'localhost',
            'smtp_port' => 587,
            'smtp_secure' => 'tls',
            'notification_sender_email' => 'noreply@condman.com',
            'notification_sender_name' => '365 Cond Man',
            'external_integration_enabled' => false,
            'log_level' => 'info'
        ];

        $success = true;
        foreach ($defaults as $key => $value) {
            $success = $success && $this->set($key, $value);
        }

        $this->logger->info('Restauração de configurações padrão', [
            'success' => $success
        ]);

        return $success;
    }

    /**
     * Exporta configurações para backup
     * 
     * @return array Configurações exportadas
     */
    public function export(): array {
        $configurations = $this->listAll();

        $this->logger->info('Exportação de configurações', [
            'total_configs' => count($configurations)
        ]);

        return $configurations;
    }

    /**
     * Importa configurações de backup
     * 
     * @param array $configurations Configurações para importar
     * @return bool Sucesso na importação
     */
    public function import(array $configurations): bool {
        $success = true;
        foreach ($configurations as $key => $value) {
            $success = $success && $this->set($key, $value);
        }

        $this->logger->info('Importação de configurações', [
            'total_configs' => count($configurations),
            'success' => $success
        ]);

        return $success;
    }
}
