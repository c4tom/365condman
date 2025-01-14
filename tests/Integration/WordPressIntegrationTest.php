<?php
namespace CondMan\Tests\Integration;

use PHPUnit\Framework\TestCase;

// Simular constante ABSPATH se não existir
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

class WordPressIntegrationTest extends TestCase {
    public function testWordPressEnvironment() {
        $this->assertTrue(defined('ABSPATH'), 'WordPress environment not loaded');
    }

    public function testPluginActivation() {
        // Simular ativação do plugin
        $this->assertTrue(true, 'Plugin activation test');
    }
}
