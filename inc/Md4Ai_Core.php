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

	private array $ai_bots;
	private Md4Ai_Cache $cache;
	private Md4Ai_Markdown $markdown;
	private Md4Ai_Admin $admin;

	public function __construct() {
		$this->ai_bots = $this->setup_ai_useragents();

		// Initialize sub-components
		$this->cache = new Md4Ai_Cache();
		$this->markdown = new Md4Ai_Markdown($this->cache);

		// Initialize REST API
		new Md4Ai_RestAPI($this->markdown);

		// Initialize metabox and admin
		$this->admin = new Md4Ai_Admin($this->cache, $this->markdown);

		// Hook into template redirect
		add_action('template_redirect', [$this, 'handle_requests'], 1);

		// Add rewrite rule for llms.txt
		add_action('init', [$this, 'add_llmstxt_rewrite']);
		add_filter('query_vars', [$this, 'add_llmstxt_query_var']);
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
	 * Add rewrite rule for llms.txt
	 */
	public function add_llmstxt_rewrite() {
		add_rewrite_rule('^llms\.txt$', 'index.php?md4ai_llmstxt=1', 'top');
	}

	/**
	 * Add query var for llms.txt
	 */
	public function add_llmstxt_query_var($vars) {
		$vars[] = 'md4ai_llmstxt';
		$vars[] = 'md4ai_md';
		return $vars;
	}

	/**
	 * Handles all requests (llms.txt or markdown for AI bots)
	 */
	public function handle_requests() {
		if (is_admin()) {
			return;
		}

		// Check if requesting llms.txt
		if (get_query_var('md4ai_llmstxt')) {
			$this->serve_llmstxt();
			return;
		}

		// Check if it's an AI bot or a request for markdown
		if (get_query_var('md4ai_md') || $this->is_ai_bot()) {
			$this->serve_markdown_to_bots();
		}
	}

	/**
	 * Serves the llms.txt content
	 */
	private function serve_llmstxt() {
		$llms_content = $this->admin->get_llms_txt_content();

		// If no content is set, provide a default message
		if (empty($llms_content)) {
			$llms_content = $this->markdown->generate_default_llmstxt();
		}

		// Set appropriate headers
		header('Content-Type: text/plain; charset=utf-8');
		header('X-Robots-Tag: noindex, nofollow');
		header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

		echo $llms_content;
		exit;
	}

	/**
	 * Serves the content in Markdown to AI bots
	 */
	private function serve_markdown_to_bots() {
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
