<?php
namespace GFC;

class Autoloader {
    public static function register() {
        spl_autoload_register([__CLASS__, 'autoload']);
    }
    
    private static function autoload($class) {
        // Only handle classes in our namespace
        if (strpos($class, 'GFC\\') !== 0) {
            return;
        }
        
        // Remove namespace from class name
        $class_name = str_replace('GFC\\', '', $class);
        
        // Convert class name to file path
        $file_path = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
        $file = plugin_dir_path(dirname(__FILE__)) . 'includes' . DIRECTORY_SEPARATOR . $file_path . '.php';
        
        // Include the file if it exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
} 