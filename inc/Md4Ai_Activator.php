<?php

namespace Md4Ai;

/**
 * Fired during plugin activation and uninstallation
 */
class Md4Ai_Activator {

	/**
	 * On activation create the default options if they don't exist
	 */
	public static function activate() {
		// Initialize dependencies
		$cache = new Md4Ai_Cache();
		$markdown = new Md4Ai_Markdown($cache);

		// generate the llms.txt content
		$llms_content = $markdown->generate_default_llmstxt();
		
		$options = array(
			'llms_content' => $llms_content
		);

		if ( ! get_option(MD4AI_OPTION) ) {
			add_option(MD4AI_OPTION, $options);
		}
	}

	/**
	 * Uninstall md4AI plugin
	 *
	 * This function is called when the plugin is uninstalled.
	 * It clears all cache files and deletes all post meta data
	 * with the key 'ai_md_custom_markdown'.
	 */
	public static function uninstall() {
		$cache = new Md4Ai_Cache();
		$cache->clear_all_cache();

		// delete all the post meta data
		delete_post_meta_by_key( '_md4ai_custom_markdown' );
	}
}
