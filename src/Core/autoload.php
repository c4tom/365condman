<?php
/**
 * Autoload simples para classes do plugin
 */

spl_autoload_register( function( $class ) {
    // Namespace base do plugin
    $prefix = 'CondMan\\';
    $base_dir = __DIR__ . '/';

    // Verificar se a classe pertence ao namespace do plugin
    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    // Remover o prefixo do namespace
    $relative_class = substr( $class, $len );

    // Substituir separadores de namespace por separadores de diretório
    $path = str_replace( '\\', '/', $relative_class );

    // Construir o caminho completo do arquivo
    $file = $base_dir . strtolower( str_replace( 'Core\\', '', $path ) ) . '.php';

    // Incluir o arquivo se existir
    if ( file_exists( $file ) ) {
        require_once $file;
    }
});
