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

require_once plugin_dir_path(__FILE__) . 'inc/main.php';

// run the plugin
new md4AI();
