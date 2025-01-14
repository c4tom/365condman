<?php
// Definir constante ABSPATH se não existir
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

// Carregar autoload do Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Configurações adicionais de teste, se necessário
ini_set('display_errors', 1);
error_reporting(E_ALL);
