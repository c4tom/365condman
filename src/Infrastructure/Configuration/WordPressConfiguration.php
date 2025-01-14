<?php
namespace CondMan\Infrastructure\Configuration;

use CondMan\Domain\Interfaces\ConfigurationInterface;

class WordPressConfiguration implements ConfigurationInterface {
    private $settings = [];

    public function __construct() {
        // Carrega configurações do WordPress
        $this->loadWordPressSettings();
    }

    private function loadWordPressSettings(): void {
        // Carrega configurações do WordPress
        $this->settings = [
            'plugin_version' => '1.0.0',
            'debug_mode' => defined('WP_DEBUG') ? WP_DEBUG : false,
            'max_condominiums' => get_option('365condman_max_condominiums', 10)
        ];
    }

    public function get(string $key, $default = null) {
        return $this->settings[$key] ?? $default;
    }

    public function set(string $key, $value): void {
        $this->settings[$key] = $value;
        
        // Atualiza configuração no WordPress
        if (function_exists('update_option')) {
            update_option("365condman_{$key}", $value);
        }
    }

    public function has(string $key): bool {
        return isset($this->settings[$key]);
    }
}
