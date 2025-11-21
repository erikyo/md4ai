<?php
/**
 * Plugin Name: md4AI
 * Description: Designed to optimise and serve content for generative engines (GEO)
 * Author: Codekraft
 * Text Domain: md4ai
 * Version: 1.0.0
 * License: GPLv2 or later
 */


if (!defined('ABSPATH')) {
	exit;
}

if (!defined('MD4AI_PLUGIN_DIR')) {
	define('MD4AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

/**
 * Option name for llms.txt content
 */
const MD4AI_OPTION = 'md4ai_options';

// Composer autoloader
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// run the plugin
function md4ai_init() {
	new md4AI_Core();
}
add_action('plugins_loaded', 'md4ai_init');

/**
 * Uninstall md4AI plugin
 *
 * This function is called when the plugin is uninstalled.
 * It clears all cache files and deletes all post meta data
 * with the key 'ai_md_custom_markdown'.
 *
 * @since 1.0.0
 */
function md4ai_uninstall() {
	$cache = new md4AI_Cache;
	$cache->clear_all_cache();

	// delete all the post meta data
	global $wpdb;
	// WordPress.DB.DirectDatabaseQuery.DirectQuery phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->delete(
		$wpdb->postmeta,
		[ "meta_key" => "ai_md_custom_markdown"],
		[ "%s"]
	);
}

register_uninstall_hook(__FILE__, 'md4ai_uninstall');
