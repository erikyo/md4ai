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
// Include the class files
require_once plugin_dir_path(__FILE__) . 'inc/class-md4ai-core.php';
require_once plugin_dir_path(__FILE__) . 'inc/class-md4ai-admin.php';
require_once plugin_dir_path(__FILE__) . 'inc/class-md4ai-cache.php';
require_once plugin_dir_path(__FILE__) . 'inc/class-md4ai-markdown.php';
require_once plugin_dir_path(__FILE__) . 'inc/class-md4ai-restapi.php';

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
	$wpdb->query( $wpdb->prepare( "DELETE FROM %i WHERE meta_key = 'ai_md_custom_markdown'", $wpdb->postmeta ) );
}

register_uninstall_hook(__FILE__, 'md4ai_uninstall');
