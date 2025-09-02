<?php
// Cache system
class Cache {
    private static $cacheDir = __DIR__ . '/../data/cache/';
    
    public static function init() {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }
    
    public static function get($key, $maxAge = 3600) {
        self::init();
        $file = self::$cacheDir . md5($key) . '.cache';
        
        if (!file_exists($file)) return null;
        
        $data = unserialize(file_get_contents($file));
        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }
        
        return $data['value'];
    }
    
    public static function set($key, $value, $ttl = 3600) {
        self::init();
        $file = self::$cacheDir . md5($key) . '.cache';
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        file_put_contents($file, serialize($data));
    }
    
    public static function delete($key) {
        self::init();
        $file = self::$cacheDir . md5($key) . '.cache';
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    public static function clear() {
        self::init();
        $files = glob(self::$cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}
