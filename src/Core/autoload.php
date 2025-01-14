<?php
/**
 * Autoload complementar para classes do plugin
 */

// Carregar o autoloader do Composer
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// Opcional: Registrar qualquer autoloader personalizado adicional
spl_autoload_register(function($class) {
    // Namespace base do plugin
    $prefix = 'CondMan\\';
    $base_dir = dirname(__DIR__) . '/';

    // Verificar se a classe pertence ao namespace do plugin
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Remover o prefixo do namespace
    $relative_class = substr($class, $len);

    // Substituir separadores de namespace por separadores de diretório
    $path = str_replace('\\', '/', $relative_class);

    // Caminho completo para o arquivo de classe
    $file = $base_dir . $path . '.php';

    // Se o arquivo existir, incluí-lo
    if (file_exists($file)) {
        require_once $file;
    }
});
