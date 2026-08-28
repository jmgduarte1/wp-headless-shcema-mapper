<?php

/**
 * Plugin Name:       Headless Angular Schema
 * Plugin URI:        https://github.com/headless-angular/headless-angular-schema
 * Description:       Maps Gutenberg content to a normalized PageSchema contract for headless Angular consumption.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Author:            Headless Angular Team
 * License:           Proprietary
 * Text Domain:       headless-angular-schema
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// PHP version requirement check.
if (version_compare(PHP_VERSION, '8.2', '<')) {
    add_action('admin_notices', static function (): void {
        ?>
        <div class="notice notice-error">
            <p>
                <?php
                echo esc_html(
                    sprintf(
                        /* translators: 1: Required PHP version, 2: Current PHP version */
                        __('Headless Angular Schema requires PHP %1$s or higher. Your server is running PHP %2$s.', 'headless-angular-schema'),
                        '8.2',
                        PHP_VERSION
                    )
                );
                ?>
            </p>
        </div>
        <?php
    });
    return;
}

// Define plugin constants.
define('HEADLESS_ANGULAR_SCHEMA_VERSION', '0.1.0');
define('HEADLESS_ANGULAR_SCHEMA_FILE', __FILE__);
define('HEADLESS_ANGULAR_SCHEMA_PATH', plugin_dir_path(__FILE__));
define('HEADLESS_ANGULAR_SCHEMA_URL', plugin_dir_url(__FILE__));

// Autoloader check.
$autoload_file = HEADLESS_ANGULAR_SCHEMA_PATH . 'vendor/autoload.php';
if (file_exists($autoload_file)) {
    require_once $autoload_file;
} else {
    // If installed without Composer vendor directory, register a fallback PSR-4 autoloader for src/
    spl_autoload_register(static function (string $class): void {
        $prefix = 'HeadlessAngular\\Schema\\';
        $base_dir = HEADLESS_ANGULAR_SCHEMA_PATH . 'src/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    });
}

// Initialize the plugin.
add_action('plugins_loaded', static function (): void {
    if (class_exists(\HeadlessAngular\Schema\Plugin::class)) {
        \HeadlessAngular\Schema\Plugin::init();
    }
});

