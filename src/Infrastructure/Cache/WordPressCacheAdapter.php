<?php
namespace CondMan\Infrastructure\Cache;

use CondMan\Domain\Interfaces\CacheInterface;

class WordPressCacheAdapter implements CacheInterface {
    private const PREFIX = '365condman_';

    public function set(string $key, $value, int $ttl = 3600): bool {
        return set_transient(self::PREFIX . $key, $value, $ttl);
    }

    public function get(string $key) {
        return get_transient(self::PREFIX . $key);
    }

    public function delete(string $key): bool {
        return delete_transient(self::PREFIX . $key);
    }

    public function clear(): bool {
        global $wpdb;
        $prefix = $wpdb->esc_like(self::PREFIX);
        $sql = $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $prefix . '%'
        );
        return $wpdb->query($sql) !== false;
    }

    public function has(string $key): bool {
        return false !== $this->get($key);
    }

    public function increment(string $key, int $step = 1) {
        $current = $this->get($key);
        if ($current === false) {
            $current = 0;
        }
        $new_value = $current + $step;
        return $this->set($key, $new_value) ? $new_value : false;
    }

    public function decrement(string $key, int $step = 1) {
        $current = $this->get($key);
        if ($current === false) {
            $current = 0;
        }
        $new_value = max(0, $current - $step);
        return $this->set($key, $new_value) ? $new_value : false;
    }
}
