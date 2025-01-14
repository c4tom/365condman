<?php
namespace CondMan\Tests\Unit;

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use CondMan\Core\Plugin;

class PluginInitializationTest extends TestCase {
    public function testPluginInitialization() {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    public function testPluginVersion() {
        $plugin = new Plugin();
        $this->assertNotEmpty($plugin->getVersion());
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $plugin->getVersion());
    }
}
