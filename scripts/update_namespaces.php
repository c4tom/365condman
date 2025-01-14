<?php

function updateNamespaces($directory) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $namespaceMap = [
        'CondMan\\' => 'CondMan\\Core\\',
        'namespace CondMan;' => 'namespace CondMan\\Core;',
    ];

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            
            foreach ($namespaceMap as $old => $new) {
                $content = str_replace($old, $new, $content);
            }

            file_put_contents($file->getPathname(), $content);
            echo "Atualizado: " . $file->getPathname() . "\n";
        }
    }
}

$projectRoot = '/projetos/wordpress-plugins/365condman';
updateNamespaces($projectRoot . '/src');
updateNamespaces($projectRoot . '/365condman.php');

echo "Atualização de namespaces concluída.\n";
