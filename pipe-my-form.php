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
add_action('plugins_loaded', static function (): void {
    // Bootstrap only: functional features will be introduced through reviewed PRs.
});
