<?php

namespace Md4Ai;

/**
 * Admin interface class - handles metaboxes, admin pages, and scripts
 */
class Md4Ai_Admin {

	/**
	 * Cache instance
	 */
	private Md4Ai_Cache $cache;

	/**
	 * Markdown instance
	 */
	private Md4Ai_Markdown $markdown;

	public function __construct($cache, $markdown) {
		$this->cache = $cache;
		$this->markdown = $markdown;

		// Add the admin menu for the admin page
		add_action('admin_menu', [$this, 'add_admin_menu']);

		// Add metabox for Markdown editing
		new Md4Ai_Metabox($this->markdown);

		// Enqueue admin scripts
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

		// Enqueue admin services scripts
		if ( Md4Ai_Utils::is_ai_service_enabled() ) {
			add_action( 'admin_enqueue_scripts', [$this, 'enqueue_admin_services' ]);
		}
	}

	/**
	 * Enqueues admin AI services scripts
	 */
	public function enqueue_admin_services() {

		$asset = include MD4AI_PLUGIN_DIR . '/build/md4ai-services.asset.php';
		$asset['dependencies'][] = 'ais-ai';

		wp_enqueue_script(
			'md4ai-services',
			plugins_url('build/md4ai-services.js', dirname(__FILE__)),
			$asset['dependencies'],
			$asset['version'],
			true
		);
	}

	/**
	 * Adds admin menu for cache management
	 */
	public function add_admin_menu() {

		$admin_views = new Md4Ai_Admin_Views($this->cache);

		add_management_page(
			'AI Markdown Cache',
			'Md4Ai',
			'manage_options',
			'md4ai',
			[$admin_views, 'render_admin_page']
		);
	}

	/**
	 * Enqueues admin scripts
	 */
	public function enqueue_admin_scripts($hook) {
		// check if the current script is loaded in the admin area or in the md4ai admin page
		if (!in_array($hook, ['post.php', 'post-new.php'] ) && get_current_screen()->base !== 'tools_page_md4ai') {
			return;
		}

		$asset = include MD4AI_PLUGIN_DIR . '/build/md4ai-admin.asset.php';

		wp_enqueue_script(
			'md4ai-admin',
			plugins_url('build/md4ai-admin.js', dirname(__FILE__)),
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'md4ai-admin',
			plugins_url('build/style-md4ai-admin.css', dirname(__FILE__)),
			[],
			$asset['version']
		);

		// Get the REST API namespace from the RestAPI class instance
		$rest_namespace = 'md4ai/v1'; // This should ideally be passed from the RestAPI class

		wp_localize_script('md4ai-admin', 'md4aiData', [
			'restUrl' => rest_url($rest_namespace ),
			'nonce' => wp_create_nonce('wp_rest' ),
			'postId' => get_the_ID(),
			'prompts' => [
				'generate-markdown' => 'You are a highly skilled SEO and GEO expert. Review the Markdown content below. Identify the key topics and generate a section of 3-5 relevant Question and Answer (Q&A) pairs to be appended to the end of the article. The Q&A should be in Markdown format, with bold questions. Output only the full, modified page content including the new Q&A section.',
				'generate-llmstxt' => 'You are a highly skilled SEO and GEO expert. Check and Enhance the current llms.txt file below to improve the Generative Engine Optimization (GEO) of the site. Output only the llms.txt content.'
			]
		]);
	}

}
