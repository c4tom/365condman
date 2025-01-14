<?php
/**
 * Plugin Name: 365 Cond Man
 * Plugin URI: https://github.com/c4tom/365condman
 * Description: Sistema de Gestão Condominial
 * Version: 1.0.0
 * Author: Candido Tominaga
 * Author URI: https://github.com/c4tom/365condman
 * Text Domain: 365condman
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Prevenir acesso direto
defined('ABSPATH') or die('Acesso direto não permitido.');

// Definir constante do arquivo do plugin
define('CONDMAN_PLUGIN_FILE', __FILE__);

// Incluir autoload do Composer
require_once __DIR__ . '/vendor/autoload.php';

use CondMan\Core\Plugin;

// Inicializar o plugin
function condman_init() {
    $plugin = new Plugin();
    $plugin->init();
}
add_action('plugins_loaded', 'condman_init');

// Registrar ativação e desativação
function condman_activate() {
    $plugin = new Plugin();
    $plugin->activate();
}
register_activation_hook(__FILE__, 'condman_activate');

function condman_deactivate() {
    $plugin = new Plugin();
    $plugin->deactivate();
}
register_deactivation_hook(__FILE__, 'condman_deactivate');
