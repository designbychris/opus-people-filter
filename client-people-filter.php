<?php
/**
 * Plugin Name: People Filter
 * Description: Search-first filtering for an Elementor Loop Grid. Prevents the People query from loading until a filter is activated.
 * Version: 0.1.5
 * Author: Chris Mitchell
 * Text Domain: people-filter
 * Requires PHP: 8.0
 */

defined('ABSPATH') || exit;

define('CPF_VERSION', '0.1.5');
define('CPF_FILE', __FILE__);
define('CPF_PATH', plugin_dir_path(__FILE__));
define('CPF_URL', plugin_dir_url(__FILE__));

require_once CPF_PATH . 'src/Plugin.php';
require_once CPF_PATH . 'src/FilterState.php';
require_once CPF_PATH . 'src/Shortcode.php';
require_once CPF_PATH . 'src/PeopleQuery.php';
require_once CPF_PATH . 'src/Assets.php';

add_action('plugins_loaded', static function (): void {
    \ClientPeopleFilter\Plugin::instance()->boot();
});
