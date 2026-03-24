<?php
/**
 * Plugin Name: md4AI
 * Description: Designed to optimise and serve content for generative engines (GEO)
 * Author: Codekraft
 * Text Domain: md4ai
 * Version: 1.3.0
 * License: GPLv2 or later
 *
 * @package Md4Ai *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'MD4AI_PLUGIN_PATH' ) ) {
	define( 'MD4AI_PLUGIN_PATH', __FILE__ );
}

if ( ! defined( 'MD4AI_PLUGIN_DIR' ) ) {
	define( 'MD4AI_PLUGIN_DIR', plugin_dir_path( MD4AI_PLUGIN_PATH ) );
}

if ( ! defined( 'MD4AI_PLUGIN_BASENAME' ) ) {
	define( 'MD4AI_PLUGIN_BASENAME', plugin_basename( MD4AI_PLUGIN_PATH ) );
}

/**
 * Option name for llms.txt content
 */
const MD4AI_OPTION = 'md4ai_options';

// Composer autoloader
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

/** Run the plugin */
function md4ai_init() {
	new Md4Ai\Md4Ai_Core();
}
add_action( 'plugins_loaded', 'md4ai_init' );

/**
 * The code that runs during plugin activation.
 * This action is documented in inc/Md4Ai_Activator.php
 */
register_activation_hook( __FILE__, array( 'Md4Ai\Md4Ai_Activator', 'activate' ) );

/**
 * The code that runs during plugin uninstallation.
 * This action is documented in inc/Md4Ai_Activator.php
 */
register_uninstall_hook( __FILE__, array( 'Md4Ai\Md4Ai_Activator', 'uninstall' ) );
