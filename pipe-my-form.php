<?php
/**
 * Plugin Name: Pipe My Form
 * Plugin URI: https://github.com/PauluxOnTheFlux97/pipe-my-form
 * Description: Interactive contact journeys and lead qualification for WordPress.
 * Version: 0.1.0-dev
 * Requires at least: 6.5
 * Requires PHP: 8.2
 * Author: Paul Maudry
 * Author URI: https://paulmaudry.fr/
 * License: GPL-3.0
 * Text Domain: pipe-my-form
 */
if (!defined('ABSPATH')) { exit; }
define('PMF_VERSION', '0.1.0-dev');
define('PMF_PLUGIN_FILE', __FILE__);
spl_autoload_register(static function (string $class): void {
    $prefix = 'PaulMaudry\\PipeMyForm\\';
    if (!str_starts_with($class, $prefix)) { return; }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) { require_once $path; }
});
add_action('init', static function (): void {
    (new \PaulMaudry\PipeMyForm\Flows\FlowRepository())->register();
});
add_action('admin_init', static function (): void {
    (new \PaulMaudry\PipeMyForm\Admin\FlowEditor())->register();
});
add_action('rest_api_init', static function (): void {
    (new \PaulMaudry\PipeMyForm\Api\FlowController())->register();
});
