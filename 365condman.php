<?php
/**
 * Plugin Name: 365 Cond Man
 * Plugin URI: https://github.com/seu-usuario/365condman
 * Description: Sistema de Gestão de Condomínio
 * Version: 0.1.0
 * Author: Sua Equipe
 * Author URI: https://github.com/seu-usuario
 * Text Domain: 365condman
 * 
 * @package CondMan
 */

// Previne acesso direto ao arquivo
if (!defined('WPINC')) {
    die;
}

// Define o diretório do plugin
if (!defined('CONDMAN_PLUGIN_DIR')) {
    define('CONDMAN_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('CONDMAN_PLUGIN_URL')) {
    define('CONDMAN_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Configura o modo de depuração se não estiver definido
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', false);
}

// Carrega o autoloader do Composer
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Autoloader personalizado para classes do plugin
spl_autoload_register(function ($class) {
    // Verifica se a classe pertence ao namespace do plugin
    $prefix = 'CondMan\\';
    $base_dir = __DIR__ . '/src/';

    // Verifica se a classe usa o prefixo do namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Remove o prefixo do namespace
    $relative_class = substr($class, $len);

    // Substitui os separadores de namespace por separadores de diretório
    $path = str_replace('\\', '/', $relative_class);

    // Monta o caminho completo do arquivo
    $file = $base_dir . $path . '.php';

    // Se o arquivo existir, inclui
    if (file_exists($file)) {
        require_once $file;
    }
});

// Inicializa o plugin
function activate_365condman() {
    \CondMan\Core\Activator::activate();
}
register_activation_hook(__FILE__, 'activate_365condman');

function deactivate_365condman() {
    \CondMan\Core\Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'deactivate_365condman');

// Inicializa os componentes principais do plugin
function condman_init() {
    $plugin = new \CondMan\Core\Plugin();
    $plugin->run();
}
add_action('plugins_loaded', 'condman_init');
