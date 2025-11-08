<?php
/**
 * Core class - handles initialization and AI bot detection
 */
class md4AI_Core {

	/**
	 * List of AI bots to detect
	 */
	private $ai_useragents = [
		'gptbot',
		'oai-searchbot',
		'chatgpt-user',
		'claudebot',
		'perplexitybot',
		'google-extended',
		'bingbot',
		'anthropic-ai',
		'cohere-ai',
		'omgilibot',
		'omgili',
		'facebookbot',
		'applebot',
		'youbot'
	];

	private $ai_bots;
	private md4AI_Cache $cache;
	private md4AI_Markdown $markdown;
	private $rest_api;
	private $admin;

	public function __construct() {
		$this->ai_bots = $this->setup_ai_useragents();

		// Initialize sub-components
		$this->cache = new md4AI_Cache();
		$this->markdown = new md4AI_Markdown($this->cache);
		$this->rest_api = new md4AI_RestAPI($this->markdown);
		$this->admin = new md4AI_Admin($this->cache, $this->markdown);

		// Hook into template redirect
		add_action('template_redirect', [$this, 'serve_markdown_to_bots'], 1);
	}

	/**
	 * Setup AI user agents with filter
	 */
	private function setup_ai_useragents() {
		return apply_filters('md4ai_ai_useragents', $this->ai_useragents);
	}

	/**
	 * Checks if the user agent matches an AI bot
	 */
	public function is_ai_bot() {
		if (!isset($_SERVER['HTTP_USER_AGENT'])) {
			return false;
		}

		$user_agent = strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])));

		foreach ($this->ai_bots as $bot) {
			if (strpos($user_agent, strtolower($bot)) !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Serves the content in Markdown to AI bots
	 */
	public function serve_markdown_to_bots() {
		// Check if it's an AI bot
		if (!$this->is_ai_bot()) {
			return;
		}

		// Get the current post
		global $post;

		if ((!$post || !is_singular()) && !is_home()) {
			return;
		}

		$markdown = false;
		$from_cache = false;

		// Try to get from cache first
		if ($this->cache->is_cache_valid($post->ID, $post->post_modified)) {
			$markdown = $this->cache->read_from_cache($post->ID);
			$from_cache = true;
		}

		// If no valid cache, get markdown and save to cache
		if ($markdown === false) {
			$markdown = $this->markdown->get_post_markdown($post);
			$this->cache->write_to_cache($post->ID, $markdown);
			$from_cache = false;
		}

		// Set headers and serve the content
		header('Content-Type: text/markdown; charset=utf-8');
		header('X-Robots-Tag: noindex, nofollow');
		header('X-Cache: ' . ($from_cache ? 'HIT' : 'MISS'));
		echo esc_textarea($markdown);
		exit;
	}
}
